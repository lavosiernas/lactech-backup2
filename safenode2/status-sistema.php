<?php
/**
 * SafeNode - Status do Sistema
 * Diagnóstico completo do funcionamento do sistema
 */

session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();
$status = [
    'timestamp' => date('Y-m-d H:i:s'),
    'sistema' => 'SafeNode',
    'versao' => '2.5.0',
    'componentes' => []
];

// 1. Banco de Dados
$status['componentes']['banco_dados'] = [
    'nome' => 'Banco de Dados',
    'status' => $db ? 'operacional' : 'erro',
    'detalhes' => []
];

if ($db) {
    try {
        // Verificar tabelas essenciais
        $tabelas = ['safenode_users', 'safenode_sites', 'safenode_security_logs', 'safenode_ip_reputation', 'safenode_behavior_patterns'];
        foreach ($tabelas as $tabela) {
            try {
                $stmt = $db->query("SELECT COUNT(*) as total FROM `$tabela`");
                $result = $stmt->fetch();
                $status['componentes']['banco_dados']['detalhes'][$tabela] = [
                    'existe' => true,
                    'registros' => (int)$result['total']
                ];
            } catch (PDOException $e) {
                $status['componentes']['banco_dados']['detalhes'][$tabela] = [
                    'existe' => false,
                    'erro' => $e->getMessage()
                ];
            }
        }
        
        // Estatísticas gerais
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_users");
        $status['componentes']['banco_dados']['detalhes']['total_usuarios'] = (int)$stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_sites");
        $status['componentes']['banco_dados']['detalhes']['total_sites'] = (int)$stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM safenode_security_logs");
        $status['componentes']['banco_dados']['detalhes']['total_logs'] = (int)$stmt->fetch()['total'];
        
    } catch (PDOException $e) {
        $status['componentes']['banco_dados']['erro'] = $e->getMessage();
    }
} else {
    $status['componentes']['banco_dados']['erro'] = 'Não foi possível conectar ao banco de dados';
}

// 2. API Dashboard Stats
$status['componentes']['api_dashboard'] = [
    'nome' => 'API Dashboard Stats',
    'status' => 'testando',
    'detalhes' => []
];

if ($db) {
    try {
        // Simular requisição à API
        $_SESSION['safenode_logged_in'] = true;
        $_SESSION['safenode_user_id'] = 1;
        $_SESSION['view_site_id'] = 0;
        
        ob_start();
        include __DIR__ . '/api/dashboard-stats.php';
        $apiOutput = ob_get_clean();
        
        $apiData = json_decode($apiOutput, true);
        if ($apiData && isset($apiData['success'])) {
            $status['componentes']['api_dashboard']['status'] = 'operacional';
            $status['componentes']['api_dashboard']['detalhes'] = [
                'retorna_json' => true,
                'success' => $apiData['success'],
                'tem_dados' => isset($apiData['data']),
                'total_requests_hoje' => $apiData['data']['today']['total_requests'] ?? 0,
                'blocked_hoje' => $apiData['data']['today']['blocked'] ?? 0
            ];
        } else {
            $status['componentes']['api_dashboard']['status'] = 'erro';
            $status['componentes']['api_dashboard']['detalhes'] = [
                'retorna_json' => false,
                'output' => substr($apiOutput, 0, 200)
            ];
        }
    } catch (Exception $e) {
        $status['componentes']['api_dashboard']['status'] = 'erro';
        $status['componentes']['api_dashboard']['erro'] = $e->getMessage();
    }
}

// 3. Middleware (SafeNodeMiddleware)
$status['componentes']['middleware'] = [
    'nome' => 'SafeNode Middleware',
    'status' => 'verificando',
    'detalhes' => []
];

$middlewarePath = __DIR__ . '/includes/SafeNodeMiddleware.php';
if (file_exists($middlewarePath)) {
    $status['componentes']['middleware']['detalhes']['arquivo_existe'] = true;
    
    // Verificar se as classes necessárias existem
    $classes = ['IPBlocker', 'RateLimiter', 'ThreatDetector', 'SecurityLogger', 'IPReputationManager', 'BehaviorAnalyzer'];
    foreach ($classes as $classe) {
        $classPath = __DIR__ . "/includes/$classe.php";
        $status['componentes']['middleware']['detalhes']["classe_$classe"] = file_exists($classPath);
    }
    
    $status['componentes']['middleware']['status'] = 'disponivel';
} else {
    $status['componentes']['middleware']['status'] = 'erro';
    $status['componentes']['middleware']['erro'] = 'Arquivo não encontrado';
}

// 4. Sistema de Proteção (Logs recentes)
$status['componentes']['sistema_protecao'] = [
    'nome' => 'Sistema de Proteção',
    'status' => 'verificando',
    'detalhes' => []
];

if ($db) {
    try {
        // Últimas 24 horas
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked,
                SUM(CASE WHEN action_taken = 'allowed' THEN 1 ELSE 0 END) as allowed
            FROM safenode_security_logs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $last24h = $stmt->fetch();
        
        // Última atividade
        $stmt = $db->query("SELECT MAX(created_at) as ultima_atividade FROM safenode_security_logs");
        $ultima = $stmt->fetch();
        
        $status['componentes']['sistema_protecao']['status'] = 'operacional';
        $status['componentes']['sistema_protecao']['detalhes'] = [
            'requisicoes_24h' => (int)($last24h['total'] ?? 0),
            'bloqueadas_24h' => (int)($last24h['blocked'] ?? 0),
            'permitidas_24h' => (int)($last24h['allowed'] ?? 0),
            'ultima_atividade' => $ultima['ultima_atividade'] ?? 'Nunca'
        ];
    } catch (PDOException $e) {
        $status['componentes']['sistema_protecao']['status'] = 'erro';
        $status['componentes']['sistema_protecao']['erro'] = $e->getMessage();
    }
}

// 5. Análise Comportamental
$status['componentes']['analise_comportamental'] = [
    'nome' => 'Análise Comportamental',
    'status' => 'verificando',
    'detalhes' => []
];

if ($db) {
    try {
        $behaviorPath = __DIR__ . '/includes/BehaviorAnalyzer.php';
        if (file_exists($behaviorPath)) {
            require_once $behaviorPath;
            $behaviorAnalyzer = new BehaviorAnalyzer($db);
            $stats = $behaviorAnalyzer->getBehaviorStats(null, null, 5);
            
            $status['componentes']['analise_comportamental']['status'] = 'operacional';
            $status['componentes']['analise_comportamental']['detalhes'] = [
                'classe_existe' => true,
                'ips_analisados' => count($stats),
                'tabela_existe' => true
            ];
        } else {
            $status['componentes']['analise_comportamental']['status'] = 'erro';
            $status['componentes']['analise_comportamental']['erro'] = 'Classe BehaviorAnalyzer não encontrada';
        }
    } catch (Exception $e) {
        $status['componentes']['analise_comportamental']['status'] = 'erro';
        $status['componentes']['analise_comportamental']['erro'] = $e->getMessage();
    }
}

// 6. Security Analytics
$status['componentes']['security_analytics'] = [
    'nome' => 'Security Analytics',
    'status' => 'verificando',
    'detalhes' => []
];

if ($db) {
    try {
        $analyticsPath = __DIR__ . '/includes/SecurityAnalytics.php';
        if (file_exists($analyticsPath)) {
            require_once $analyticsPath;
            $securityAnalytics = new SecurityAnalytics($db);
            
            $status['componentes']['security_analytics']['status'] = 'operacional';
            $status['componentes']['security_analytics']['detalhes'] = [
                'classe_existe' => true,
                'metodos_disponiveis' => [
                    'getAttackPatternsByTime' => method_exists($securityAnalytics, 'getAttackPatternsByTime'),
                    'getSuspiciousIPs' => method_exists($securityAnalytics, 'getSuspiciousIPs'),
                    'getMostAttackedTargets' => method_exists($securityAnalytics, 'getMostAttackedTargets'),
                    'generateInsights' => method_exists($securityAnalytics, 'generateInsights')
                ]
            ];
        } else {
            $status['componentes']['security_analytics']['status'] = 'erro';
            $status['componentes']['security_analytics']['erro'] = 'Classe SecurityAnalytics não encontrada';
        }
    } catch (Exception $e) {
        $status['componentes']['security_analytics']['status'] = 'erro';
        $status['componentes']['security_analytics']['erro'] = $e->getMessage();
    }
}

// 7. Páginas Principais
$status['componentes']['paginas'] = [
    'nome' => 'Páginas do Sistema',
    'status' => 'verificando',
    'detalhes' => []
];

$paginas = [
    'dashboard.php' => 'Dashboard',
    'sites.php' => 'Gerenciar Sites',
    'behavior-analysis.php' => 'Análise Comportamental',
    'security-analytics.php' => 'Security Analytics',
    'suspicious-ips.php' => 'IPs Suspeitos',
    'attacked-targets.php' => 'Alvos Atacados'
];

foreach ($paginas as $arquivo => $nome) {
    $caminho = __DIR__ . '/' . $arquivo;
    $status['componentes']['paginas']['detalhes'][$arquivo] = [
        'nome' => $nome,
        'existe' => file_exists($caminho),
        'legivel' => file_exists($caminho) ? is_readable($caminho) : false
    ];
}

$todasExistem = true;
foreach ($status['componentes']['paginas']['detalhes'] as $pag) {
    if (!$pag['existe']) {
        $todasExistem = false;
        break;
    }
}

$status['componentes']['paginas']['status'] = $todasExistem ? 'operacional' : 'parcial';

// Calcular status geral
$operacionais = 0;
$comErro = 0;
$total = count($status['componentes']);

foreach ($status['componentes'] as $comp) {
    if ($comp['status'] === 'operacional' || $comp['status'] === 'disponivel') {
        $operacionais++;
    } elseif ($comp['status'] === 'erro') {
        $comErro++;
    }
}

$status['resumo'] = [
    'total_componentes' => $total,
    'operacionais' => $operacionais,
    'com_erro' => $comErro,
    'em_teste' => $total - $operacionais - $comErro,
    'percentual_funcional' => round(($operacionais / $total) * 100, 1)
];

// Verificar se é requisição AJAX (retornar JSON) ou página normal (retornar HTML)
$isAjax = isset($_GET['format']) && $_GET['format'] === 'json';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Retornar HTML
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status do Sistema | SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: #000; color: #fff; }
        .status-badge { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
        }
        .status-operacional { background: rgba(52, 211, 153, 0.1); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.2); }
        .status-erro { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        .status-verificando { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }
        .status-disponivel { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }
        .status-parcial { background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.2); }
    </style>
</head>
<body class="min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="activity" class="w-8 h-8 text-blue-400"></i>
                Status do Sistema SafeNode
            </h1>
            <p class="text-zinc-400">Última verificação: <?php echo $status['timestamp']; ?></p>
        </div>

        <!-- Resumo -->
        <div class="mb-8 p-6 rounded-xl bg-zinc-900 border border-white/10">
            <h2 class="text-xl font-bold mb-4">Resumo Geral</h2>
            <div class="grid grid-cols-4 gap-4">
                <div class="p-4 rounded-lg bg-zinc-800">
                    <div class="text-2xl font-bold text-white"><?php echo $status['resumo']['total_componentes']; ?></div>
                    <div class="text-sm text-zinc-400">Componentes</div>
                </div>
                <div class="p-4 rounded-lg bg-emerald-500/10 border border-emerald-500/20">
                    <div class="text-2xl font-bold text-emerald-400"><?php echo $status['resumo']['operacionais']; ?></div>
                    <div class="text-sm text-emerald-400">Operacionais</div>
                </div>
                <div class="p-4 rounded-lg bg-red-500/10 border border-red-500/20">
                    <div class="text-2xl font-bold text-red-400"><?php echo $status['resumo']['com_erro']; ?></div>
                    <div class="text-sm text-red-400">Com Erro</div>
                </div>
                <div class="p-4 rounded-lg bg-blue-500/10 border border-blue-500/20">
                    <div class="text-2xl font-bold text-blue-400"><?php echo $status['resumo']['percentual_funcional']; ?>%</div>
                    <div class="text-sm text-blue-400">Funcional</div>
                </div>
            </div>
        </div>

        <!-- Componentes -->
        <div class="space-y-4">
            <?php foreach ($status['componentes'] as $key => $comp): ?>
                <div class="p-6 rounded-xl bg-zinc-900 border border-white/10">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($comp['nome']); ?></h3>
                        <span class="status-badge status-<?php echo $comp['status']; ?>">
                            <?php 
                            $icon = 'check-circle';
                            if ($comp['status'] === 'erro') $icon = 'x-circle';
                            elseif ($comp['status'] === 'verificando') $icon = 'loader';
                            elseif ($comp['status'] === 'disponivel') $icon = 'check';
                            ?>
                            <i data-lucide="<?php echo $icon; ?>" class="w-4 h-4"></i>
                            <?php echo ucfirst($comp['status']); ?>
                        </span>
                    </div>
                    
                    <?php if (isset($comp['erro'])): ?>
                        <div class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
                            <strong>Erro:</strong> <?php echo htmlspecialchars($comp['erro']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($comp['detalhes'])): ?>
                        <div class="mt-4 space-y-2">
                            <?php foreach ($comp['detalhes'] as $key => $value): ?>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-zinc-400"><?php echo htmlspecialchars($key); ?>:</span>
                                    <span class="text-white font-mono">
                                        <?php 
                                        if (is_bool($value)) {
                                            echo $value ? '✅ Sim' : '❌ Não';
                                        } elseif (is_array($value)) {
                                            echo json_encode($value, JSON_UNESCAPED_UNICODE);
                                        } else {
                                            echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-8 text-center text-zinc-500 text-sm">
            <a href="?format=json" class="text-blue-400 hover:text-blue-300">Ver como JSON</a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php

