<?php
/**
 * KRON - Visualização de Métricas
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/KronRBAC.php';
require_once __DIR__ . '/../includes/KronSystemManager.php';

requireAuth();
requirePermission('metrics.read');

$user = getCurrentUser();
$systemManager = new KronSystemManager();
$pdo = getKronDatabase();

$systems = $systemManager->listSystems('active');
$selectedSystem = $_GET['system'] ?? null;
$selectedMetric = $_GET['metric'] ?? null;
$days = (int)($_GET['days'] ?? 7);

// Buscar métricas
$metrics = [];
$chartData = [];

if ($pdo) {
    $sql = "
        SELECT m.*, s.name as system_name, s.display_name as system_display_name
        FROM kron_metrics m
        INNER JOIN kron_systems s ON m.system_id = s.id
        WHERE m.metric_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
    ";
    $params = [$days];
    
    if ($selectedSystem) {
        $sql .= " AND m.system_id = ?";
        $params[] = $selectedSystem;
    }
    
    if ($selectedMetric) {
        $sql .= " AND m.metric_type = ?";
        $params[] = $selectedMetric;
    }
    
    $sql .= " ORDER BY m.metric_date DESC, m.metric_hour DESC LIMIT 1000";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $metrics = $stmt->fetchAll();
    
    // Preparar dados para gráfico
    $grouped = [];
    foreach ($metrics as $m) {
        $key = $m['system_name'] . '|' . $m['metric_type'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'system' => $m['system_display_name'],
                'type' => $m['metric_type'],
                'data' => []
            ];
        }
        $grouped[$key]['data'][] = [
            'date' => $m['metric_date'],
            'value' => (float)$m['metric_value']
        ];
    }
    $chartData = array_values($grouped);
}

// Tipos de métricas únicos
$metricTypes = [];
if ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT metric_type FROM kron_metrics ORDER BY metric_type");
    $metricTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas - KRON</title>
    <link rel="icon" type="image/png" href="../asset/kron.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #0a0a0a; color: #f5f5f7; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/_sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2">Métricas</h1>
            <p class="text-gray-400">Visualize métricas dos sistemas governados</p>
        </div>
        
        <!-- Filtros -->
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-6 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Sistema</label>
                    <select name="system"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                        <option value="">Todos</option>
                        <?php foreach ($systems as $sys): ?>
                            <option value="<?= $sys['id'] ?>" <?= $selectedSystem == $sys['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sys['display_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Tipo de Métrica</label>
                    <select name="metric"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                        <option value="">Todos</option>
                        <?php foreach ($metricTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $selectedMetric === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Período</label>
                    <select name="days"
                        class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 focus:outline-none focus:border-blue-500">
                        <option value="1" <?= $days === 1 ? 'selected' : '' ?>>Último dia</option>
                        <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Últimos 7 dias</option>
                        <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Últimos 30 dias</option>
                        <option value="90" <?= $days === 90 ? 'selected' : '' ?>>Últimos 90 dias</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 rounded-lg font-medium">
                        Filtrar
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Gráfico -->
        <?php if (!empty($chartData)): ?>
            <div class="bg-gray-900 rounded-xl border border-gray-800 p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Gráfico de Métricas</h2>
                <canvas id="metricsChart" height="100"></canvas>
            </div>
        <?php endif; ?>
        
        <!-- Tabela de Métricas -->
        <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
            <div class="p-6 border-b border-gray-800">
                <h2 class="text-xl font-bold">Métricas Recentes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-800">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Data/Hora</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Sistema</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Tipo</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Valor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        <?php if (empty($metrics)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                    Nenhuma métrica encontrada
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach (array_slice($metrics, 0, 50) as $metric): ?>
                                <tr class="hover:bg-gray-800/50">
                                    <td class="px-6 py-4 text-sm">
                                        <?= date('d/m/Y H:i', strtotime($metric['metric_date'] . ' ' . ($metric['metric_hour'] ?? 0) . ':00:00')) ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium"><?= htmlspecialchars($metric['system_display_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded bg-gray-700 text-gray-300">
                                            <?= htmlspecialchars($metric['metric_type']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold">
                                        <?= number_format($metric['metric_value'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        <?php if (!empty($chartData)): ?>
        const ctx = document.getElementById('metricsChart').getContext('2d');
        const chartData = <?= json_encode($chartData) ?>;
        
        const datasets = chartData.map((series, index) => {
            const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
            return {
                label: series.system + ' - ' + series.type,
                data: series.data.map(d => ({x: d.date, y: d.value})),
                borderColor: colors[index % colors.length],
                backgroundColor: colors[index % colors.length] + '20',
                tension: 0.4
            };
        });
        
        new Chart(ctx, {
            type: 'line',
            data: { datasets },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { labels: { color: '#f5f5f7' } }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day' },
                        ticks: { color: '#9ca3af' },
                        grid: { color: '#1f2937' }
                    },
                    y: {
                        ticks: { color: '#9ca3af' },
                        grid: { color: '#1f2937' }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>



