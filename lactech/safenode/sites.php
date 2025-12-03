<?php
/**
 * SafeNode - Gerenciar Sites
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

$pageTitle = 'Gerenciar Sites';
$currentSiteId = $_SESSION['view_site_id'] ?? 0;
$userId = $_SESSION['safenode_user_id'] ?? null;
$selectedSite = null;

$db = getSafeNodeDatabase();
if ($db && $currentSiteId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$currentSiteId, $userId]);
        $selectedSite = $stmt->fetch();
    } catch (PDOException $e) {
        $selectedSite = null;
    }
}

$message = '';
$messageType = '';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRFProtection::validate()) {
        $message = "Token de segurança inválido.";
        $messageType = "error";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            if (!$db) {
                $message = 'Erro: Não foi possível conectar ao banco de dados';
                $messageType = 'error';
            } elseif (!$userId) {
                $message = 'Erro: Usuário não identificado. Faça login novamente';
                $messageType = 'error';
            } else {
                $domain = trim($_POST['domain'] ?? '');
                $displayName = trim($_POST['display_name'] ?? '');
                $securityLevel = $_POST['security_level'] ?? 'medium';
                
                if (empty($domain)) {
                    $message = 'Domínio é obrigatório';
                    $messageType = 'error';
                } else {
                    // Validar formato do domínio
                    $domain = strtolower($domain);
                    $domain = preg_replace('/^https?:\/\//', '', $domain);
                    $domain = preg_replace('/\/$/', '', $domain);
                    
                    if (!InputValidator::domain($domain)) {
                        $message = 'Formato de domínio inválido';
                        $messageType = 'error';
                    } else {
                        try {
                            // Verificar se já existe
                            $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE domain = ? AND user_id = ?");
                            $stmt->execute([$domain, $userId]);
                            if ($stmt->fetch()) {
                                $message = 'Este domínio já está cadastrado';
                                $messageType = 'error';
                            } else {
                                $stmt = $db->prepare("
                                    INSERT INTO safenode_sites 
                                    (user_id, domain, display_name, security_level, is_active, created_at, updated_at) 
                                    VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                                ");
                                $stmt->execute([$userId, $domain, $displayName ?: $domain, $securityLevel]);
                                $message = 'Site cadastrado com sucesso!';
                                $messageType = 'success';
                            }
                        } catch (PDOException $e) {
                            error_log("SafeNode Create Site Error: " . $e->getMessage());
                            $message = 'Erro ao cadastrar site';
                            $messageType = 'error';
                        }
                    }
                }
            }
        } elseif ($action === 'delete' && $db) {
            $siteId = (int)($_POST['site_id'] ?? 0);
            if ($siteId > 0) {
                try {
                    $stmt = $db->prepare("DELETE FROM safenode_sites WHERE id = ? AND user_id = ?");
                    $stmt->execute([$siteId, $userId]);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Site removido com sucesso';
                        $messageType = 'success';
                    } else {
                        $message = 'Site não encontrado';
                        $messageType = 'error';
                    }
                } catch (PDOException $e) {
                    error_log("SafeNode Delete Site Error: " . $e->getMessage());
                    $message = 'Erro ao remover site';
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'toggle' && $db) {
            $siteId = (int)($_POST['site_id'] ?? 0);
            if ($siteId > 0) {
                try {
                    $stmt = $db->prepare("UPDATE safenode_sites SET is_active = NOT is_active WHERE id = ? AND user_id = ?");
                    $stmt->execute([$siteId, $userId]);
                    $message = 'Status do site atualizado';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    error_log("SafeNode Toggle Site Error: " . $e->getMessage());
                    $message = 'Erro ao atualizar site';
                    $messageType = 'error';
                }
            }
        }
    }
}

// Buscar sites do usuário
$sites = [];
if ($db && $userId) {
    try {
        $stmt = $db->prepare("
            SELECT id, domain, display_name, security_level, is_active, created_at, updated_at,
                   (SELECT COUNT(*) FROM safenode_security_logs WHERE site_id = safenode_sites.id) as total_logs
            FROM safenode_sites 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SafeNode List Sites Error: " . $e->getMessage());
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
<body class="h-full">
    <div class="flex h-full">
        <!-- Sidebar -->
        <aside class="sidebar w-72 h-full flex-shrink-0 flex flex-col hidden lg:flex">
            <div class="p-6 border-b border-white/5">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain">
                        <div>
                            <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                            <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <nav class="flex-1 p-5 space-y-2 overflow-y-auto">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Menu Principal</p>
                
                <a href="dashboard.php" class="nav-item">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span class="font-medium">Home</span>
                </a>
                <a href="sites.php" class="nav-item active">
                    <i data-lucide="globe" class="w-5 h-5"></i>
                    <span class="font-medium">Sites</span>
                </a>
                <a href="security-analytics.php" class="nav-item">
                    <i data-lucide="activity" class="w-5 h-5"></i>
                    <span class="font-medium">Network</span>
                </a>
                <a href="behavior-analysis.php" class="nav-item">
                    <i data-lucide="cpu" class="w-5 h-5"></i>
                    <span class="font-medium">Kubernetes</span>
                </a>
                <a href="logs.php" class="nav-item">
                    <i data-lucide="compass" class="w-5 h-5"></i>
                    <span class="font-medium">Explorar</span>
                </a>
                <a href="suspicious-ips.php" class="nav-item">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span class="font-medium">Analisar</span>
                </a>
                <a href="attacked-targets.php" class="nav-item">
                    <i data-lucide="users-2" class="w-5 h-5"></i>
                    <span class="font-medium">Grupos</span>
                </a>
                
                <div class="pt-6 mt-6 border-t border-white/5">
                    <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-4 px-3">Sistema</p>
                    <a href="human-verification.php" class="nav-item">
                        <i data-lucide="shield-check" class="w-5 h-5"></i>
                        <span class="font-medium">Verificação Humana</span>
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i data-lucide="settings-2" class="w-5 h-5"></i>
                        <span class="font-medium">Configurações</span>
                    </a>
                    <a href="help.php" class="nav-item">
                        <i data-lucide="life-buoy" class="w-5 h-5"></i>
                        <span class="font-medium">Ajuda</span>
                    </a>
                </div>
            </nav>
            
            <div class="p-5">
                <div class="upgrade-card">
                    <h3 class="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
                    <button class="w-full btn-primary py-2.5 text-sm">
                        Upgrade Agora
                    </button>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full overflow-hidden bg-dark-950">
            <!-- Header -->
            <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-6">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">Gerenciar Sites</h2>
                        <?php if ($selectedSite): ?>
                            <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['domain'] ?? ''); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <?php if ($message): ?>
                <div class="glass rounded-2xl p-4 mb-6 <?php echo $messageType === 'success' ? 'border-green-500/30 bg-green-500/10' : ($messageType === 'error' ? 'border-red-500/30 bg-red-500/10' : 'border-amber-500/30 bg-amber-500/10'); ?>">
                    <p class="text-white"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <?php endif; ?>

                <!-- Formulário de Cadastro -->
                <div class="glass rounded-2xl p-6 mb-6">
                    <h3 class="text-xl font-semibold text-white mb-6 flex items-center gap-3">
                        <i data-lucide="plus-circle" class="w-6 h-6"></i>
                        Cadastrar Novo Site
                    </h3>
                    <form method="POST" class="space-y-6">
                        <?php echo CSRFProtection::getTokenField(); ?>
                        <input type="hidden" name="action" value="create">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Domínio *</label>
                                <input type="text" name="domain" required 
                                    placeholder="exemplo.com" 
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all">
                                <p class="text-xs text-zinc-400 mt-2">Apenas o domínio, sem http:// ou https://</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Nome de Exibição</label>
                                <input type="text" name="display_name" 
                                    placeholder="Meu Site Principal" 
                                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all">
                                <p class="text-xs text-zinc-400 mt-2">Nome amigável (opcional)</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Nível de Segurança</label>
                            <select name="security_level" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all">
                                <option value="low">Baixo - Menos bloqueios</option>
                                <option value="medium" selected>Médio - Balanceado</option>
                                <option value="high">Alto - Mais bloqueios</option>
                            </select>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="btn-primary px-6 py-2.5 rounded-xl text-sm">
                                <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                                Cadastrar Site
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Lista de Sites -->
                <div class="glass rounded-2xl p-6">
                    <h3 class="text-xl font-semibold text-white mb-6 flex items-center gap-3">
                        <i data-lucide="list" class="w-6 h-6"></i>
                        Meus Sites (<?php echo count($sites); ?>)
                    </h3>
                    
                    <?php if (empty($sites)): ?>
                        <div class="text-center py-16">
                            <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="globe" class="w-8 h-8 text-zinc-400"></i>
                            </div>
                            <p class="text-zinc-400 font-medium mb-2">Nenhum site cadastrado ainda</p>
                            <p class="text-sm text-zinc-500">Cadastre seu primeiro site acima</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($sites as $site): ?>
                                <div class="p-6 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-3">
                                                <h4 class="text-lg font-semibold text-white">
                                                    <?php echo htmlspecialchars($site['display_name'] ?: $site['domain']); ?>
                                                </h4>
                                                <?php if ($site['is_active']): ?>
                                                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Ativo</span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-zinc-500/20 text-zinc-400 border border-zinc-500/30">Inativo</span>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $levelColors = [
                                                    'low' => 'blue',
                                                    'medium' => 'amber',
                                                    'high' => 'red',
                                                    'under_attack' => 'red'
                                                ];
                                                $levelLabels = [
                                                    'low' => 'Baixo',
                                                    'medium' => 'Médio',
                                                    'high' => 'Alto',
                                                    'under_attack' => 'Sob Ataque'
                                                ];
                                                $levelColor = $levelColors[$site['security_level']] ?? 'amber';
                                                $levelLabel = $levelLabels[$site['security_level']] ?? 'Médio';
                                                
                                                if ($levelColor === 'blue') {
                                                    $badgeClass = 'bg-blue-500/20 text-blue-400 border-blue-500/30';
                                                } elseif ($levelColor === 'amber') {
                                                    $badgeClass = 'bg-amber-500/20 text-amber-400 border-amber-500/30';
                                                } else {
                                                    $badgeClass = 'bg-red-500/20 text-red-400 border-red-500/30';
                                                }
                                                ?>
                                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold <?php echo $badgeClass; ?>">
                                                    Segurança: <?php echo $levelLabel; ?>
                                                </span>
                                            </div>
                                            
                                            <p class="text-sm text-zinc-400 font-mono mb-2"><?php echo htmlspecialchars($site['domain']); ?></p>
                                            <p class="text-xs text-zinc-500">
                                                <?php echo (int)$site['total_logs']; ?> eventos registrados • 
                                                Cadastrado em <?php echo date('d/m/Y', strtotime($site['created_at'])); ?>
                                            </p>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 ml-6">
                                            <form method="POST" class="inline">
                                                <?php echo CSRFProtection::getTokenField(); ?>
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                                <button type="submit" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-semibold transition-colors">
                                                    <?php echo $site['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                                </button>
                                            </form>
                                            
                                            <a href="dashboard.php?view_site=<?php echo $site['id']; ?>" 
                                               class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-semibold transition-colors">
                                                Ver Dashboard
                                            </a>
                                            
                                            <form method="POST" class="inline" 
                                                  onsubmit="return confirm('Tem certeza que deseja remover este site? Esta ação não pode ser desfeita.');">
                                                <?php echo CSRFProtection::getTokenField(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                                <button type="submit" class="px-4 py-2 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-400 text-sm font-semibold transition-colors border border-red-500/30">
                                                    Remover
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
    </script>
    
    <!-- Security Scripts - Previne download de código -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>
