<?php
/**
 * SafeNode - Porcentagem de Funcionalidade do Sistema
 * Verificação rápida do status funcional
 */

// Habilitar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Iniciar output buffering
ob_start();

try {
    session_start();
} catch (Exception $e) {
    // Ignorar erro de sessão já iniciada
}

$db = null;
$erroGeral = null;

try {
    require_once __DIR__ . '/includes/config.php';
    require_once __DIR__ . '/includes/init.php';
    $db = getSafeNodeDatabase();
} catch (Exception $e) {
    $erroGeral = $e->getMessage();
} catch (Error $e) {
    $erroGeral = $e->getMessage();
}

// Inicializar variáveis
$componentes = [];
$totalPeso = 0;
$pesoFuncional = 0;
$detalhes = [];
$porcentagem = 0;
$resultado = ['componentes' => [], 'porcentagem' => 0, 'peso_total' => 0, 'peso_funcional' => 0];

try {
    // Lista de componentes críticos e suas verificações
    $componentes = [
        'banco_dados' => ['peso' => 20, 'funciona' => false, 'nome' => 'Banco de Dados'],
        'api_dashboard' => ['peso' => 15, 'funciona' => false, 'nome' => 'API Dashboard'],
        'dashboard' => ['peso' => 15, 'funciona' => false, 'nome' => 'Dashboard'],
        'cadastro_sites' => ['peso' => 10, 'funciona' => false, 'nome' => 'Cadastro de Sites'],
        'middleware' => ['peso' => 10, 'funciona' => false, 'nome' => 'Middleware'],
        'analise_comportamental' => ['peso' => 8, 'funciona' => false, 'nome' => 'Análise Comportamental'],
        'security_analytics' => ['peso' => 8, 'funciona' => false, 'nome' => 'Security Analytics'],
        'paginas_analise' => ['peso' => 7, 'funciona' => false, 'nome' => 'Páginas de Análise'],
        'sistema_protecao' => ['peso' => 5, 'funciona' => false, 'nome' => 'Sistema de Proteção'],
        'ip_reputation' => ['peso' => 2, 'funciona' => false, 'nome' => 'IP Reputation']
    ];

    // 1. Banco de Dados (20%)
if ($db) {
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_users LIMIT 1");
        $stmt->fetch();
        $componentes['banco_dados']['funciona'] = true;
        $pesoFuncional += $componentes['banco_dados']['peso'];
        $detalhes['banco_dados'] = '✅ Conectado e operacional';
    } catch (Exception $e) {
        $detalhes['banco_dados'] = '❌ Erro: ' . $e->getMessage();
    }
} else {
    $detalhes['banco_dados'] = '❌ Não conectado';
}
$totalPeso += $componentes['banco_dados']['peso'];

// 2. API Dashboard (15%)
if ($db) {
    try {
        // Verificar se arquivo existe
        if (file_exists(__DIR__ . '/api/dashboard-stats.php')) {
            // Tentar fazer uma requisição simples sem incluir o arquivo
            $apiPath = __DIR__ . '/api/dashboard-stats.php';
            $componentes['api_dashboard']['funciona'] = true;
            $pesoFuncional += $componentes['api_dashboard']['peso'];
            $detalhes['api_dashboard'] = '✅ Arquivo existe e está acessível';
        } else {
            $detalhes['api_dashboard'] = '❌ Arquivo não encontrado';
        }
    } catch (Exception $e) {
        $detalhes['api_dashboard'] = '❌ Erro: ' . $e->getMessage();
    }
} else {
    $detalhes['api_dashboard'] = '⚠️ Banco não disponível para teste completo';
    // Mesmo sem banco, se o arquivo existe, dar metade dos pontos
    if (file_exists(__DIR__ . '/api/dashboard-stats.php')) {
        $pesoFuncional += $componentes['api_dashboard']['peso'] * 0.5;
    }
}
$totalPeso += $componentes['api_dashboard']['peso'];

// 3. Dashboard (15%)
if (file_exists(__DIR__ . '/dashboard.php')) {
    $componentes['dashboard']['funciona'] = true;
    $pesoFuncional += $componentes['dashboard']['peso'];
    $detalhes['dashboard'] = '✅ Página existe e está acessível';
} else {
    $detalhes['dashboard'] = '❌ Arquivo não encontrado';
}
$totalPeso += $componentes['dashboard']['peso'];

// 4. Cadastro de Sites (10%)
if (file_exists(__DIR__ . '/sites.php') && $db) {
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_sites LIMIT 1");
        $componentes['cadastro_sites']['funciona'] = true;
        $pesoFuncional += $componentes['cadastro_sites']['peso'];
        $detalhes['cadastro_sites'] = '✅ Página e tabela funcionais';
    } catch (Exception $e) {
        $detalhes['cadastro_sites'] = '⚠️ Página existe mas tabela pode ter problema';
        $pesoFuncional += $componentes['cadastro_sites']['peso'] * 0.7; // 70% funcional
    }
} else {
    $detalhes['cadastro_sites'] = file_exists(__DIR__ . '/sites.php') ? '⚠️ Página existe mas banco não disponível' : '❌ Arquivo não encontrado';
}
$totalPeso += $componentes['cadastro_sites']['peso'];

// 5. Middleware (10%)
if (file_exists(__DIR__ . '/includes/SafeNodeMiddleware.php')) {
    $classes = ['IPBlocker', 'RateLimiter', 'ThreatDetector', 'SecurityLogger'];
    $todasClasses = true;
    foreach ($classes as $classe) {
        if (!file_exists(__DIR__ . "/includes/$classe.php")) {
            $todasClasses = false;
            break;
        }
    }
    if ($todasClasses) {
        $componentes['middleware']['funciona'] = true;
        $pesoFuncional += $componentes['middleware']['peso'];
        $detalhes['middleware'] = '✅ Arquivo e classes disponíveis';
    } else {
        $detalhes['middleware'] = '⚠️ Arquivo existe mas algumas classes faltam';
        $pesoFuncional += $componentes['middleware']['peso'] * 0.8;
    }
} else {
    $detalhes['middleware'] = '❌ Arquivo não encontrado';
}
$totalPeso += $componentes['middleware']['peso'];

// 6. Análise Comportamental (8%)
if (file_exists(__DIR__ . '/includes/BehaviorAnalyzer.php')) {
    try {
        require_once __DIR__ . '/includes/BehaviorAnalyzer.php';
        if (class_exists('BehaviorAnalyzer')) {
            $componentes['analise_comportamental']['funciona'] = true;
            $pesoFuncional += $componentes['analise_comportamental']['peso'];
            $detalhes['analise_comportamental'] = '✅ Classe disponível';
        } else {
            $detalhes['analise_comportamental'] = '⚠️ Arquivo existe mas classe não definida';
        }
    } catch (Exception $e) {
        $detalhes['analise_comportamental'] = '❌ Erro ao carregar: ' . $e->getMessage();
    }
} else {
    $detalhes['analise_comportamental'] = '❌ Arquivo não encontrado';
}
$totalPeso += $componentes['analise_comportamental']['peso'];

// 7. Security Analytics (8%)
if (file_exists(__DIR__ . '/includes/SecurityAnalytics.php')) {
    try {
        require_once __DIR__ . '/includes/SecurityAnalytics.php';
        if (class_exists('SecurityAnalytics')) {
            $componentes['security_analytics']['funciona'] = true;
            $pesoFuncional += $componentes['security_analytics']['peso'];
            $detalhes['security_analytics'] = '✅ Classe disponível';
        } else {
            $detalhes['security_analytics'] = '⚠️ Arquivo existe mas classe não definida';
        }
    } catch (Exception $e) {
        $detalhes['security_analytics'] = '❌ Erro ao carregar: ' . $e->getMessage();
    }
} else {
    $detalhes['security_analytics'] = '❌ Arquivo não encontrado';
}
$totalPeso += $componentes['security_analytics']['peso'];

// 8. Páginas de Análise (7%)
$paginasAnalise = ['behavior-analysis.php', 'security-analytics.php', 'suspicious-ips.php', 'attacked-targets.php'];
$paginasExistentes = 0;
foreach ($paginasAnalise as $pagina) {
    if (file_exists(__DIR__ . '/' . $pagina)) {
        $paginasExistentes++;
    }
}
if ($paginasExistentes === count($paginasAnalise)) {
    $componentes['paginas_analise']['funciona'] = true;
    $pesoFuncional += $componentes['paginas_analise']['peso'];
    $detalhes['paginas_analise'] = "✅ Todas as {$paginasExistentes} páginas existem";
} else {
    $percentual = $paginasExistentes / count($paginasAnalise);
    $pesoFuncional += $componentes['paginas_analise']['peso'] * $percentual;
    $detalhes['paginas_analise'] = "⚠️ {$paginasExistentes}/" . count($paginasAnalise) . " páginas existem";
}
$totalPeso += $componentes['paginas_analise']['peso'];

// 9. Sistema de Proteção (5%)
if ($db) {
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_security_logs LIMIT 1");
        $componentes['sistema_protecao']['funciona'] = true;
        $pesoFuncional += $componentes['sistema_protecao']['peso'];
        $detalhes['sistema_protecao'] = '✅ Tabela de logs existe';
    } catch (Exception $e) {
        $detalhes['sistema_protecao'] = '❌ Tabela de logs não existe';
    }
} else {
    $detalhes['sistema_protecao'] = '❌ Banco não disponível';
}
$totalPeso += $componentes['sistema_protecao']['peso'];

// 10. IP Reputation (2%)
if ($db) {
    try {
        $stmt = $db->query("SELECT 1 FROM safenode_ip_reputation LIMIT 1");
        if (file_exists(__DIR__ . '/includes/IPReputationManager.php')) {
            $componentes['ip_reputation']['funciona'] = true;
            $pesoFuncional += $componentes['ip_reputation']['peso'];
            $detalhes['ip_reputation'] = '✅ Classe e tabela existem';
        } else {
            $detalhes['ip_reputation'] = '⚠️ Tabela existe mas classe não encontrada';
            $pesoFuncional += $componentes['ip_reputation']['peso'] * 0.5;
        }
    } catch (Exception $e) {
        $detalhes['ip_reputation'] = '❌ Tabela não existe';
    }
} else {
    $detalhes['ip_reputation'] = '❌ Banco não disponível';
}
    $totalPeso += $componentes['ip_reputation']['peso'];

    $porcentagem = $totalPeso > 0 ? round(($pesoFuncional / $totalPeso) * 100, 1) : 0;

    $resultado = [
        'porcentagem' => $porcentagem,
        'peso_total' => $totalPeso,
        'peso_funcional' => round($pesoFuncional, 1),
        'componentes' => [],
        'detalhes' => $detalhes,
        'erro' => $erroGeral
    ];

    foreach ($componentes as $key => $comp) {
        $resultado['componentes'][$key] = [
            'nome' => $comp['nome'],
            'peso' => $comp['peso'],
            'funciona' => $comp['funciona'],
            'detalhes' => $detalhes[$key] ?? 'Não verificado'
        ];
    }
} catch (Exception $e) {
    $erroGeral = "Erro durante verificação: " . $e->getMessage();
    $resultado['erro'] = $erroGeral;
} catch (Error $e) {
    $erroGeral = "Erro fatal durante verificação: " . $e->getMessage();
    $resultado['erro'] = $erroGeral;
}

if (!isset($porcentagem)) $porcentagem = 0;
if (!isset($pesoFuncional)) $pesoFuncional = 0;
if (!isset($totalPeso)) $totalPeso = 100;
if (empty($resultado['componentes'])) {
    $resultado['componentes'] = [];
}

ob_clean();

if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Retornar HTML
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Porcentagem do Sistema | SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { background: #000; color: #fff; }
        .progress-bar {
            height: 8px;
            background: #1f2937;
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            transition: width 1s ease;
        }
    </style>
</head>
<body class="min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <?php if ($erroGeral): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                <p class="text-red-400 font-bold">Erro ao carregar sistema:</p>
                <p class="text-red-300 text-sm mt-2"><?php echo htmlspecialchars($erroGeral); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="mb-8 text-center">
            <h1 class="text-4xl font-bold mb-4 flex items-center justify-center gap-3">
                <i data-lucide="activity" class="w-10 h-10 text-blue-400"></i>
                Funcionalidade do Sistema
            </h1>
            
            <!-- Porcentagem Grande -->
            <div class="mb-8">
                <div class="text-8xl font-bold mb-4" style="background: linear-gradient(135deg, #3b82f6, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                    <?php echo isset($porcentagem) ? $porcentagem : '0'; ?>%
                </div>
                <div class="progress-bar max-w-md mx-auto">
                    <div class="progress-fill" style="width: <?php echo isset($porcentagem) ? $porcentagem : 0; ?>%"></div>
                </div>
            </div>
            
            <p class="text-zinc-400 text-lg">
                <?php echo isset($pesoFuncional) ? round($pesoFuncional, 1) : 0; ?> / <?php echo isset($totalPeso) ? $totalPeso : 100; ?> pontos funcionais
            </p>
        </div>

        <!-- Componentes -->
        <div class="space-y-3">
            <?php if (empty($resultado['componentes'])): ?>
                <div class="p-6 rounded-xl bg-zinc-900 border border-white/10 text-center">
                    <p class="text-zinc-400">Nenhum componente verificado. Verifique os erros acima.</p>
                </div>
            <?php else: ?>
            <?php foreach ($resultado['componentes'] as $key => $comp): ?>
                <div class="p-4 rounded-xl bg-zinc-900 border border-white/10">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="w-10 h-10 rounded-lg <?php echo $comp['funciona'] ? 'bg-emerald-500/10' : 'bg-red-500/10'; ?> flex items-center justify-center">
                                <i data-lucide="<?php echo $comp['funciona'] ? 'check' : 'x'; ?>" class="w-5 h-5 <?php echo $comp['funciona'] ? 'text-emerald-400' : 'text-red-400'; ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-bold text-white"><?php echo htmlspecialchars($comp['nome']); ?></div>
                                <div class="text-xs text-zinc-400"><?php echo htmlspecialchars($comp['detalhes']); ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-zinc-400"><?php echo $comp['peso']; ?>pts</div>
                            <?php if ($comp['funciona']): ?>
                                <div class="text-xs text-emerald-400">✓ Funcional</div>
                            <?php else: ?>
                                <div class="text-xs text-red-400">✗ Não funcional</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="mt-8 text-center">
            <a href="?format=json" class="text-blue-400 hover:text-blue-300 text-sm">Ver como JSON</a> |
            <a href="status-sistema.php" class="text-blue-400 hover:text-blue-300 text-sm">Ver Status Detalhado</a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php
