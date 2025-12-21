<?php
/**
 * SafeNode - Security Advisor
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
require_once __DIR__ . '/includes/SecurityAdvisor.php';

$pageTitle = 'Security Advisor';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

$db = getSafeNodeDatabase();
$advisor = new SecurityAdvisor($db);

// Buscar sites do usuário
$userSites = [];
if ($db && $userId) {
    try {
        $stmt = $db->prepare("SELECT id, domain, display_name FROM safenode_sites WHERE user_id = ? ORDER BY display_name ASC");
        $stmt->execute([$userId]);
        $userSites = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao buscar sites: " . $e->getMessage());
    }
}

$message = '';
$messageType = '';

// Executar auditoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_audit']) && $currentSiteId > 0) {
    $result = $advisor->runSecurityAudit($currentSiteId);
    if ($result) {
        $message = 'Auditoria executada com sucesso! Score: ' . $result['score'] . '/100';
        $messageType = 'success';
    } else {
        $message = 'Erro ao executar auditoria';
        $messageType = 'error';
    }
}

// Aplicar recomendação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_recommendation']) && $currentSiteId > 0) {
    $recId = (int)$_POST['apply_recommendation'];
    $applied = $advisor->applyRecommendation($recId);
    if ($applied) {
        $message = 'Recomendação aplicada com sucesso!';
        $messageType = 'success';
    } else {
        $message = 'Não foi possível aplicar automaticamente. Configure manualmente.';
        $messageType = 'warning';
    }
    // Recarregar recomendações
    $recommendations = $advisor->getRecommendations($currentSiteId, 'pending');
}

// Obter recomendações
$recommendations = [];
if ($currentSiteId > 0) {
    $recommendations = $advisor->getRecommendations($currentSiteId, 'pending');
}

// Obter última auditoria
$lastAudit = null;
$auditHistory = [];
if ($db && $currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_security_audits WHERE site_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$currentSiteId]);
        $lastAudit = $stmt->fetch();
        
        // Obter histórico de auditorias
        $auditHistory = $advisor->getAuditHistory($currentSiteId, 10);
    } catch (PDOException $e) {
        error_log("Erro ao buscar auditoria: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    },
                    colors: {
                        dark: {
                            950: '#030303',
                            900: '#050505',
                            850: '#080808',
                            800: '#0a0a0a',
                            700: '#0f0f0f',
                            600: '#141414',
                            500: '#1a1a1a',
                            400: '#222222',
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --bg-primary: #030303;
            --bg-secondary: #080808;
            --bg-tertiary: #0f0f0f;
            --bg-card: #0a0a0a;
            --bg-hover: #111111;
            --border-subtle: rgba(255,255,255,0.04);
            --border-light: rgba(255,255,255,0.08);
            --accent: #ffffff;
            --accent-glow: rgba(255, 255, 255, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #a1a1aa;
            --text-muted: #52525b;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-secondary);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            font-size: 0.92em;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { 
            background: rgba(255,255,255,0.1); 
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.15); }
        
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-subtle);
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border-right: 1px solid var(--border-subtle);
            position: relative;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            border-radius: 12px;
            color: var(--text-muted);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
        }
        
        .nav-item:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text-primary);
        }
        
        .nav-item.active {
            background: rgba(255,255,255,0.1);
            color: var(--text-primary);
        }
        
        .upgrade-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 16px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #e5e5e5 100%);
            color: #000;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px rgba(255, 255, 255, 0.5);
        }
        
        .btn-primary:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    <div class="flex h-full">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
            <!-- Header -->
            <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-6">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight"><?php echo $pageTitle; ?></h2>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">Security Advisor</h1>
                    <p class="text-zinc-400">Auditoria automática e recomendações de segurança</p>
                </div>
                
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-500/20 text-green-400 border border-green-500/30' : 'bg-red-500/20 text-red-400 border border-red-500/30'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>
                
                <?php if ($currentSiteId === 0): ?>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-8 text-center">
                    <i data-lucide="globe" class="w-16 h-16 text-zinc-600 mx-auto mb-4"></i>
                    <p class="text-zinc-400 mb-6">Selecione um site para executar a auditoria</p>
                    <?php if (!empty($userSites)): ?>
                    <div class="max-w-md mx-auto" x-data="{ open: false }">
                        <button @click="open = !open" class="bg-white text-black px-6 py-3 rounded-lg font-semibold hover:bg-zinc-200 transition-colors inline-flex items-center gap-2">
                            <i data-lucide="globe" class="w-4 h-4"></i>
                            Selecionar Site
                            <i data-lucide="chevron-down" class="w-4 h-4" :class="{ 'rotate-180': open }"></i>
                        </button>
                        <div x-show="open" 
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="mt-4 bg-dark-700 border border-white/10 rounded-lg shadow-xl overflow-hidden"
                             style="display: none;">
                            <div class="max-h-64 overflow-y-auto py-2">
                                <?php foreach ($userSites as $site): ?>
                                <a href="<?php 
                                    require_once __DIR__ . '/includes/SecurityToken.php';
                                    $tokenMgr = new SecurityToken();
                                    $urlToken = $tokenMgr->generateToken($_SESSION['safenode_user_id'] ?? null, $site['id']);
                                    echo '?token=' . $urlToken . '&view_site=' . $site['id'];
                                ?>" class="block px-4 py-3 text-sm text-zinc-300 hover:bg-white/5 hover:text-white transition-colors">
                                    <div class="font-medium"><?php echo htmlspecialchars($site['display_name'] ?: $site['domain']); ?></div>
                                    <div class="text-xs text-zinc-500 font-mono"><?php echo htmlspecialchars($site['domain']); ?></div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="sites.php" class="inline-block bg-white text-black px-6 py-3 rounded-lg font-semibold hover:bg-zinc-200 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                        Cadastrar Primeiro Site
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                
                <div class="mb-6">
                    <form method="POST" class="inline-block">
                        <button type="submit" name="run_audit" class="bg-white text-black px-6 py-3 rounded-lg font-semibold hover:bg-zinc-200 transition-colors">
                            <i data-lucide="play" class="w-4 h-4 inline mr-2"></i>
                            Executar Auditoria
                        </button>
                    </form>
                </div>
                
                <?php if ($lastAudit): 
                    $auditDetails = $advisor->getAuditDetails($lastAudit['id']);
                    $score = (int)$lastAudit['security_score'];
                    $scoreColor = $score >= 80 ? 'text-green-400' : ($score >= 60 ? 'text-yellow-400' : 'text-red-400');
                    $scoreBg = $score >= 80 ? 'bg-green-500/10 border-green-500/30' : ($score >= 60 ? 'bg-yellow-500/10 border-yellow-500/30' : 'bg-red-500/10 border-red-500/30');
                ?>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-white">Última Auditoria</h2>
                        <span class="text-sm text-zinc-400">
                            <?php echo date('d/m/Y H:i', strtotime($lastAudit['created_at'])); ?>
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div class="<?php echo $scoreBg; ?> border rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Score Geral</div>
                            <div class="text-3xl font-bold <?php echo $scoreColor; ?>"><?php echo $score; ?>/100</div>
                            <div class="mt-2 w-full bg-dark-700 rounded-full h-2">
                                <div class="<?php echo $score >= 80 ? 'bg-green-500' : ($score >= 60 ? 'bg-yellow-500' : 'bg-red-500'); ?> h-2 rounded-full" style="width: <?php echo $score; ?>%"></div>
                            </div>
                        </div>
                        <div class="bg-dark-700 border border-white/5 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Aprovados</div>
                            <div class="text-2xl font-bold text-green-400"><?php echo $lastAudit['passed_checks']; ?></div>
                        </div>
                        <div class="bg-dark-700 border border-white/5 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Falhas</div>
                            <div class="text-2xl font-bold text-red-400"><?php echo $lastAudit['failed_checks']; ?></div>
                        </div>
                        <div class="bg-dark-700 border border-white/5 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Avisos</div>
                            <div class="text-2xl font-bold text-yellow-400"><?php echo $lastAudit['warnings']; ?></div>
                        </div>
                        <div class="bg-dark-700 border border-white/5 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Total</div>
                            <div class="text-2xl font-bold text-white"><?php echo $lastAudit['total_checks']; ?></div>
                        </div>
                    </div>
                    
                    <?php if ($auditDetails && !empty($auditDetails['results'])): 
                        $resultsByCategory = [];
                        foreach ($auditDetails['results'] as $result) {
                            $category = $result['check_category'];
                            if (!isset($resultsByCategory[$category])) {
                                $resultsByCategory[$category] = [];
                            }
                            $resultsByCategory[$category][] = $result;
                        }
                    ?>
                    <div class="border-t border-white/10 pt-6">
                        <h3 class="text-lg font-bold text-white mb-4">Detalhes por Categoria</h3>
                        <div class="space-y-4">
                            <?php foreach ($resultsByCategory as $category => $results): 
                                $categoryPassed = 0;
                                $categoryFailed = 0;
                                $categoryWarnings = 0;
                                foreach ($results as $result) {
                                    if ($result['status'] === 'pass') $categoryPassed++;
                                    elseif ($result['status'] === 'fail') $categoryFailed++;
                                    else $categoryWarnings++;
                                }
                                $categoryTotal = count($results);
                            ?>
                            <div class="bg-dark-700 border border-white/5 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="font-semibold text-white capitalize"><?php echo str_replace('_', ' ', $category); ?></h4>
                                    <div class="flex gap-3 text-sm">
                                        <span class="text-green-400">✓ <?php echo $categoryPassed; ?></span>
                                        <span class="text-red-400">✗ <?php echo $categoryFailed; ?></span>
                                        <?php if ($categoryWarnings > 0): ?>
                                        <span class="text-yellow-400">⚠ <?php echo $categoryWarnings; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <?php foreach ($results as $result): 
                                        $statusColor = $result['status'] === 'pass' ? 'text-green-400' : ($result['status'] === 'fail' ? 'text-red-400' : 'text-yellow-400');
                                        $statusIcon = $result['status'] === 'pass' ? 'check-circle' : ($result['status'] === 'fail' ? 'x-circle' : 'alert-triangle');
                                        $severityBadge = $result['severity'] === 'critical' ? 'bg-red-500/20 text-red-400' : 
                                                         ($result['severity'] === 'high' ? 'bg-orange-500/20 text-orange-400' : 
                                                         ($result['severity'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-blue-500/20 text-blue-400'));
                                    ?>
                                    <div class="flex items-start gap-3 p-2 rounded bg-dark-800/50">
                                        <i data-lucide="<?php echo $statusIcon; ?>" class="w-4 h-4 <?php echo $statusColor; ?> mt-0.5 flex-shrink-0"></i>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <span class="text-sm font-medium text-white"><?php echo htmlspecialchars($result['check_name']); ?></span>
                                                <span class="px-2 py-0.5 text-xs font-medium rounded <?php echo $severityBadge; ?>">
                                                    <?php echo ucfirst($result['severity']); ?>
                                                </span>
                                            </div>
                                            <div class="text-xs text-zinc-400">
                                                <span class="font-medium">Atual:</span> <?php echo htmlspecialchars($result['current_value']); ?>
                                                <?php if ($result['status'] !== 'pass' && $result['recommended_value']): ?>
                                                <span class="ml-3 font-medium">Recomendado:</span> <?php echo htmlspecialchars($result['recommended_value']); ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($result['fix_instructions']): ?>
                                            <div class="mt-1 text-xs text-zinc-500">
                                                <?php echo nl2br(htmlspecialchars($result['fix_instructions'])); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-white">Recomendações Pendentes</h2>
                        <span class="text-sm text-zinc-400"><?php echo count($recommendations); ?> pendente(s)</span>
                    </div>
                    
                    <?php if (empty($recommendations)): ?>
                    <div class="text-center py-12">
                        <i data-lucide="check-circle" class="w-16 h-16 text-green-400 mx-auto mb-4"></i>
                        <p class="text-zinc-400 font-medium mb-1">Nenhuma recomendação pendente</p>
                        <p class="text-zinc-500 text-sm">Todas as recomendações foram aplicadas ou não há problemas detectados</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($recommendations as $rec): 
                            $priorityBadge = $rec['priority'] === 'critical' ? 'bg-red-500/20 text-red-400 border-red-500/30' : 
                                            ($rec['priority'] === 'high' ? 'bg-orange-500/20 text-orange-400 border-orange-500/30' : 
                                            ($rec['priority'] === 'medium' ? 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30' : 'bg-blue-500/20 text-blue-400 border-blue-500/30'));
                            $typeBadge = 'bg-white/5 text-zinc-300 border-white/10';
                        ?>
                        <div class="border border-white/10 rounded-lg p-5 hover:border-white/20 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="font-semibold text-white text-lg"><?php echo htmlspecialchars($rec['title']); ?></h3>
                                        <span class="px-2 py-1 rounded text-xs font-bold border <?php echo $priorityBadge; ?>">
                                            <?php echo strtoupper($rec['priority']); ?>
                                        </span>
                                        <span class="px-2 py-1 rounded text-xs font-medium border <?php echo $typeBadge; ?> capitalize">
                                            <?php echo str_replace('_', ' ', $rec['recommendation_type']); ?>
                                        </span>
                                    </div>
                                    <p class="text-zinc-400 text-sm leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($rec['description']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-3 border-t border-white/5">
                                <div class="flex items-center gap-4 text-xs text-zinc-500">
                                    <span><i data-lucide="trending-up" class="w-3 h-3 inline mr-1"></i> Impacto: <?php echo ucfirst($rec['impact'] ?? 'Médio'); ?></span>
                                    <span><i data-lucide="clock" class="w-3 h-3 inline mr-1"></i> Esforço: <?php echo ucfirst($rec['effort'] ?? 'Médio'); ?></span>
                                </div>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="apply_recommendation" value="<?php echo $rec['id']; ?>">
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                        <i data-lucide="zap" class="w-4 h-4"></i>
                                        Aplicar Automaticamente
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Histórico de Auditorias -->
                <?php if (!empty($auditHistory)): ?>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6 mt-6">
                    <h2 class="text-xl font-bold text-white mb-4">Histórico de Auditorias</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Data</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Score</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Aprovados</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Falhas</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Status</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditHistory as $audit): 
                                    $score = (int)($audit['security_score'] ?? 0);
                                    $scoreColor = $score >= 80 ? 'text-green-400' : ($score >= 60 ? 'text-yellow-400' : 'text-red-400');
                                ?>
                                <tr class="border-b border-white/5 hover:bg-white/5">
                                    <td class="py-3 px-4 text-sm text-zinc-300"><?php echo date('d/m/Y H:i', strtotime($audit['created_at'])); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-3 py-1 rounded text-sm font-bold <?php 
                                            echo $score >= 80 ? 'bg-green-500/20 text-green-400' : 
                                                ($score >= 60 ? 'bg-yellow-500/20 text-yellow-400' : 'bg-red-500/20 text-red-400'); 
                                        ?>">
                                            <?php echo $score; ?>/100
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-green-400"><?php echo $audit['passed_checks']; ?></td>
                                    <td class="py-3 px-4 text-sm text-red-400"><?php echo $audit['failed_checks']; ?></td>
                                    <td class="py-3 px-4 text-sm <?php echo $audit['status'] === 'completed' ? 'text-green-400' : ($audit['status'] === 'running' ? 'text-yellow-400' : 'text-red-400'); ?>">
                                        <?php echo ucfirst($audit['status']); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <a href="?view_audit=<?php echo $audit['id']; ?>" class="text-blue-400 hover:text-blue-300 text-sm">
                                            Ver Detalhes
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>

