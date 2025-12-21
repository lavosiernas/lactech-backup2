<?php
/**
 * SafeNode - Security Tests
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
require_once __DIR__ . '/includes/SecurityTestSimulator.php';

$pageTitle = 'Testes de Segurança';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;

$db = getSafeNodeDatabase();
$simulator = new SecurityTestSimulator($db);

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

// Buscar testes
$tests = [];
if ($db && $currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_security_tests WHERE site_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$currentSiteId, $userId]);
        $tests = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao buscar testes: " . $e->getMessage());
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
                    <button data-sidebar-toggle class="lg:hidden text-zinc-400 hover:text-white transition-colors">
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
                    <h1 class="text-3xl font-bold text-white mb-2">Testes de Segurança</h1>
                    <p class="text-zinc-400">Testes controlados e autorizados de segurança</p>
                </div>
                
                <?php if ($currentSiteId === 0): ?>
                <div class="bg-dark-800 border border-white/10 rounded-xl p-8 text-center">
                    <i data-lucide="globe" class="w-16 h-16 text-zinc-600 mx-auto mb-4"></i>
                    <p class="text-zinc-400 mb-6">Selecione um site para criar testes de segurança</p>
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
                    <button class="bg-white text-black px-6 py-3 rounded-lg font-semibold hover:bg-zinc-200 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                        Novo Teste
                    </button>
                </div>
                
                <div class="bg-dark-800 border border-white/10 rounded-xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4">Testes Realizados</h2>
                    <?php if (empty($tests)): ?>
                    <p class="text-zinc-400 text-center py-8">Nenhum teste realizado ainda</p>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-white/10">
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Nome</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Tipo</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Status</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Score</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Requisições</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Bloqueadas</th>
                                    <th class="text-left py-3 px-4 text-zinc-400 text-sm font-medium">Data</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tests as $test): ?>
                                <tr class="border-b border-white/5 hover:bg-white/5">
                                    <td class="py-3 px-4 text-sm text-white font-medium"><?php echo htmlspecialchars($test['test_name']); ?></td>
                                    <td class="py-3 px-4 text-sm text-zinc-300"><?php echo ucfirst(str_replace('_', ' ', $test['test_type'])); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-2 py-1 rounded text-xs font-medium <?php 
                                            echo $test['status'] === 'completed' ? 'bg-green-500/20 text-green-400' : 
                                                ($test['status'] === 'running' ? 'bg-blue-500/20 text-blue-400' : 
                                                ($test['status'] === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-yellow-500/20 text-yellow-400')); 
                                        ?>">
                                            <?php echo ucfirst($test['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-sm text-zinc-300">
                                        <?php echo $test['test_score']; ?>/100
                                    </td>
                                    <td class="py-3 px-4 text-sm text-zinc-300"><?php echo number_format($test['total_requests']); ?></td>
                                    <td class="py-3 px-4 text-sm text-zinc-300"><?php echo number_format($test['blocked_requests']); ?></td>
                                    <td class="py-3 px-4 text-sm text-zinc-400"><?php echo date('d/m/Y H:i', strtotime($test['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>

