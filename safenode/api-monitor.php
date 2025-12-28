<?php
/**
 * SafeNode - Monitoramento de API de Verificação Humana
 */

session_start();

require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/HVAPIKeyManager.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

// Obter API keys do usuário
$apiKeys = HVAPIKeyManager::getUserKeys($userId);

// Parâmetros
$selectedKeyId = isset($_GET['key_id']) ? (int)$_GET['key_id'] : null;
$period = $_GET['period'] ?? '24h';

// Validar período
$validPeriods = ['1h', '24h', '7d', '30d'];
if (!in_array($period, $validPeriods)) {
    $period = '24h';
}

// Se não há API keys, redirecionar
if (empty($apiKeys)) {
    header('Location: human-verification.php');
    exit;
}

// Se não há key selecionada, usar a primeira
if (!$selectedKeyId) {
    $selectedKeyId = $apiKeys[0]['id'];
}

// Validar que a key pertence ao usuário
$selectedKey = null;
foreach ($apiKeys as $key) {
    if ($key['id'] === $selectedKeyId) {
        $selectedKey = $key;
        break;
    }
}

if (!$selectedKey) {
    $selectedKeyId = $apiKeys[0]['id'];
    $selectedKey = $apiKeys[0];
}

// Obter estatísticas
$stats = HVAPIKeyManager::getAllStats($selectedKeyId, $userId, $period);
$usageStats = $stats['usage'] ?? [];
$perfStats = $stats['performance'] ?? [];

// Labels de período
$periodLabels = [
    '1h' => 'Última Hora',
    '24h' => 'Últimas 24 Horas',
    '7d' => 'Últimos 7 Dias',
    '30d' => 'Últimos 30 Dias'
];

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoramento de API | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: #030303;
            color: #a1a1aa;
            font-family: 'Inter', sans-serif;
            font-size: 0.92em;
            -webkit-font-smoothing: antialiased;
        }
        
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Estilizar select e opções */
        select {
            background-color: rgba(255, 255, 255, 0.05) !important;
            color: #ffffff !important;
            cursor: pointer;
        }
        
        select:focus {
            background-color: rgba(255, 255, 255, 0.08) !important;
        }
        
        select option {
            background-color: #0a0a0a !important;
            color: #ffffff !important;
            padding: 12px 16px !important;
            border: none !important;
        }
        
        select option:hover {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        
        select option:checked,
        select option:focus {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
        }
        
        /* Para navegadores baseados em WebKit (Chrome, Safari, Edge) */
        select::-webkit-scrollbar {
            width: 8px;
        }
        
        select::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        
        select::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        select::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-screen bg-dark-950 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="human-verification.php" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white mb-4">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Voltar para Verificação Humana</span>
                </a>
                <h1 class="text-3xl font-bold text-white mb-2">Monitoramento de API</h1>
                <p class="text-zinc-500">Acompanhe o uso e desempenho da sua API de verificação humana</p>
            </div>

            <!-- Filtros -->
            <div class="glass rounded-2xl p-6 mb-8">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-zinc-400 mb-2">API Key</label>
                        <select 
                            id="keySelect" 
                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/10 appearance-none cursor-pointer"
                            style="background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'white\'><path d=\'M7 10l5 5 5-5z\'/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;"
                            onchange="window.location.href='?key_id=' + this.value + '&period=<?php echo $period; ?>'"
                        >
                            <?php foreach ($apiKeys as $key): ?>
                                <option value="<?php echo $key['id']; ?>" <?php echo $key['id'] === $selectedKeyId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($key['name']); ?> (<?php echo htmlspecialchars(substr($key['api_key'], 0, 20)); ?>...)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-zinc-400 mb-2">Período</label>
                        <select 
                            id="periodSelect" 
                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/10 appearance-none cursor-pointer"
                            style="background-image: url('data:image/svg+xml;charset=UTF-8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'white\'><path d=\'M7 10l5 5 5-5z\'/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1.5em 1.5em; padding-right: 2.5rem;"
                            onchange="window.location.href='?key_id=<?php echo $selectedKeyId; ?>&period=' + this.value"
                        >
                            <?php foreach ($validPeriods as $p): ?>
                                <option value="<?php echo $p; ?>" <?php echo $p === $period ? 'selected' : ''; ?>>
                                    <?php echo $periodLabels[$p]; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Métricas Principais -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="glass rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
                            <i data-lucide="activity" class="w-6 h-6 text-blue-400"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-1"><?php echo number_format($usageStats['total'] ?? 0); ?></h3>
                    <p class="text-sm text-zinc-400">Total de Requisições</p>
                </div>

                <div class="glass rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
                            <i data-lucide="check-circle" class="w-6 h-6 text-green-400"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-1"><?php echo number_format($usageStats['success'] ?? 0); ?></h3>
                    <p class="text-sm text-zinc-400">Requisições Bem-sucedidas</p>
                </div>

                <div class="glass rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center">
                            <i data-lucide="x-circle" class="w-6 h-6 text-red-400"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-1"><?php echo number_format($usageStats['failed'] ?? 0); ?></h3>
                    <p class="text-sm text-zinc-400">Requisições Falhadas</p>
                </div>

                <div class="glass rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-6 h-6 text-purple-400"></i>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-1"><?php echo number_format($usageStats['success_rate'] ?? 0, 1); ?>%</h3>
                    <p class="text-sm text-zinc-400">Taxa de Sucesso</p>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Gráfico de Requisições por Hora -->
                <div class="glass rounded-2xl p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">Requisições por Hora</h2>
                    <div style="position: relative; height: 300px;">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>

                <!-- Distribuição por Tipo -->
                <div class="glass rounded-2xl p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">Distribuição por Tipo</h2>
                    <div style="position: relative; height: 300px;">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tabelas de Dados -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Top IPs -->
                <div class="glass rounded-2xl p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">IPs Mais Frequentes</h2>
                    <div class="space-y-3">
                        <?php if (!empty($usageStats['top_ips'])): ?>
                            <?php foreach ($usageStats['top_ips'] as $ip): ?>
                                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="globe" class="w-4 h-4 text-zinc-400"></i>
                                        <span class="text-white font-mono text-sm"><?php echo htmlspecialchars($ip['ip']); ?></span>
                                    </div>
                                    <span class="text-zinc-400 text-sm"><?php echo number_format($ip['count']); ?> requisições</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-zinc-500 text-center py-4">Nenhum dado disponível</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Domínios -->
                <div class="glass rounded-2xl p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">Domínios Mais Frequentes</h2>
                    <div class="space-y-3">
                        <?php if (!empty($usageStats['top_domains'])): ?>
                            <?php foreach ($usageStats['top_domains'] as $domain): ?>
                                <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="link" class="w-4 h-4 text-zinc-400"></i>
                                        <span class="text-white text-sm"><?php echo htmlspecialchars($domain['domain']); ?></span>
                                    </div>
                                    <span class="text-zinc-400 text-sm"><?php echo number_format($domain['count']); ?> requisições</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-zinc-500 text-center py-4">Nenhum dado disponível</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Detalhes por Tipo -->
            <div class="glass rounded-2xl p-6">
                <h2 class="text-xl font-semibold text-white mb-4">Detalhes por Tipo de Requisição</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <?php 
                    $typeLabels = [
                        'init' => 'Inicializações',
                        'validate' => 'Validações',
                        'failed' => 'Falhas',
                        'suspicious' => 'Suspeitas'
                    ];
                    $typeColors = [
                        'init' => 'blue',
                        'validate' => 'green',
                        'failed' => 'red',
                        'suspicious' => 'yellow'
                    ];
                    foreach ($typeLabels as $type => $label): 
                        $count = $usageStats['by_type'][$type] ?? 0;
                        $color = $typeColors[$type] ?? 'zinc';
                    ?>
                        <div class="p-4 bg-white/5 rounded-xl">
                            <p class="text-sm text-zinc-400 mb-2"><?php echo $label; ?></p>
                            <p class="text-2xl font-bold text-white"><?php echo number_format($count); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Dados para gráficos
        const hourlyData = <?php echo json_encode($usageStats['hourly'] ?? []); ?>;
        const distributionData = <?php echo json_encode($perfStats['distribution'] ?? []); ?>;
        const byTypeData = <?php echo json_encode($usageStats['by_type'] ?? []); ?>;

        // Configuração de cores
        const chartColors = {
            background: 'rgba(255, 255, 255, 0.05)',
            border: 'rgba(255, 255, 255, 0.1)',
            text: '#a1a1aa',
            success: '#10b981',
            failed: '#ef4444',
            init: '#3b82f6',
            validate: '#10b981'
        };

        // Gráfico de Requisições por Hora
        const hourlyCtx = document.getElementById('hourlyChart');
        if (hourlyCtx && hourlyData.length > 0) {
            const labels = hourlyData.map(d => {
                const date = new Date(d.hour);
                return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            });
            const successData = hourlyData.map(d => d.success);
            const failedData = hourlyData.map(d => d.failed);

            new Chart(hourlyCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Sucessos',
                            data: successData,
                            borderColor: chartColors.success,
                            backgroundColor: chartColors.success + '20',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: chartColors.success,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: chartColors.success,
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3
                        },
                        {
                            label: 'Falhas',
                            data: failedData,
                            borderColor: chartColors.failed,
                            backgroundColor: chartColors.failed + '20',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: chartColors.failed,
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverBackgroundColor: chartColors.failed,
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: {
                        legend: {
                            labels: { color: chartColors.text }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    },
                    scales: {
                        x: {
                            ticks: { color: chartColors.text },
                            grid: { color: chartColors.border }
                        },
                        y: {
                            ticks: { color: chartColors.text },
                            grid: { color: chartColors.border },
                            beginAtZero: true,
                            min: 0
                        }
                    }
                }
            });
        }

        // Gráfico de Distribuição por Tipo
        const typeCtx = document.getElementById('typeChart');
        if (typeCtx && distributionData.length > 0) {
            const labels = distributionData.map(d => {
                const typeLabels = {
                    'init': 'Inicializações',
                    'validate': 'Validações',
                    'failed': 'Falhas',
                    'suspicious': 'Suspeitas'
                };
                return typeLabels[d.type] || d.type;
            });
            const data = distributionData.map(d => d.count);
            const colors = distributionData.map(d => {
                const colorMap = {
                    'init': '#3b82f6',
                    'validate': '#10b981',
                    'failed': '#ef4444',
                    'suspicious': '#eab308'
                };
                return colorMap[d.type] || '#6b7280';
            });

            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        borderColor: '#030303',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 1.5,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: chartColors.text }
                        }
                    }
                }
            });
        }
    </script>
    
    <!-- Security Scripts -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>

