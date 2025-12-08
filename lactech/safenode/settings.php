<?php
/**
 * SafeNode - Configurações do Sistema
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
require_once __DIR__ . '/includes/Settings.php';

$pageTitle = 'Configurações';
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

// Processar atualização de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    if (!CSRFProtection::validate()) {
        $message = "Token de segurança inválido.";
        $messageType = "error";
    } else {
        if ($db) {
            try {
                $settings = $_POST['settings'] ?? [];
                $updated = 0;
                
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("UPDATE safenode_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ? AND is_editable = 1");
                    $stmt->execute([$value, $key]);
                    if ($stmt->rowCount() > 0) {
                        $updated++;
                    }
                }
                
                if ($updated > 0) {
                    $message = "Configurações atualizadas com sucesso!";
                    $messageType = "success";
                } else {
                    $message = "Nenhuma configuração foi atualizada.";
                    $messageType = "warning";
                }
            } catch (PDOException $e) {
                error_log("Settings Update Error: " . $e->getMessage());
                $message = "Erro ao atualizar configurações.";
                $messageType = "error";
            }
        }
    }
}

// Buscar todas as configurações
$allSettings = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM safenode_settings ORDER BY category, setting_key");
        $allSettings = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Settings Fetch Error: " . $e->getMessage());
    }
}

// Agrupar por categoria
$settingsByCategory = [];
foreach ($allSettings as $setting) {
    $category = $setting['category'] ?? 'general';
    if (!isset($settingsByCategory[$category])) {
        $settingsByCategory[$category] = [];
    }
    $settingsByCategory[$category][] = $setting;
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
                <a href="sites.php" class="nav-item">
                    <i data-lucide="globe" class="w-5 h-5"></i>
                    <span class="font-medium">Gerenciar Sites</span>
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
                    <a href="settings.php" class="nav-item active">
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

    <!-- Mobile Sidebar Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/80 z-40 lg:hidden"
         x-cloak
         style="display: none;"></div>

    <!-- Mobile Sidebar -->
    <aside x-show="sidebarOpen"
           x-transition:enter="transition ease-out duration-300 transform"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-300 transform"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @click.away="sidebarOpen = false"
           class="fixed inset-y-0 left-0 w-72 sidebar h-full flex flex-col z-50 lg:hidden overflow-y-auto"
           x-cloak
           style="display: none;">
        <!-- Logo -->
        <div class="p-4 border-b border-white/5 flex-shrink-0 relative">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="assets/img/logos (6).png" alt="SafeNode Logo" class="w-8 h-8 object-contain flex-shrink-0">
                    <div class="overflow-hidden whitespace-nowrap">
                        <h1 class="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                        <p class="text-xs text-zinc-500 font-medium">Security Platform</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Navigation -->
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
            <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Menu Principal</p>
            
            <a href="dashboard.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="layout-dashboard" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Home</span>
            </a>
            <a href="sites.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="globe" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Gerenciar Sites</span>
            </a>
            <a href="security-analytics.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="activity" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Network</span>
            </a>
            <a href="behavior-analysis.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="cpu" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Kubernetes</span>
            </a>
            <a href="logs.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="compass" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Explorar</span>
            </a>
            <a href="suspicious-ips.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="bar-chart-3" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Analisar</span>
            </a>
            <a href="attacked-targets.php" class="nav-item" @click="sidebarOpen = false">
                <i data-lucide="users-2" class="w-5 h-5 flex-shrink-0"></i>
                <span class="font-medium whitespace-nowrap">Grupos</span>
            </a>
            
            <div class="pt-4 mt-4 border-t border-white/5">
                <p class="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">Sistema</p>
                <a href="human-verification.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="shield-check" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Verificação Humana</span>
                </a>
                <a href="settings.php" class="nav-item active" @click="sidebarOpen = false">
                    <i data-lucide="settings-2" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Configurações</span>
                </a>
                <a href="help.php" class="nav-item" @click="sidebarOpen = false">
                    <i data-lucide="life-buoy" class="w-5 h-5 flex-shrink-0"></i>
                    <span class="font-medium whitespace-nowrap">Ajuda</span>
                </a>
            </div>
        </nav>
        
        <!-- Upgrade Card -->
        <div class="p-4 flex-shrink-0">
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
                        <h2 class="text-2xl font-bold text-white tracking-tight">Configurações</h2>
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

                <form method="POST" action="settings.php">
                    <?php echo CSRFProtection::getTokenField(); ?>
                    <input type="hidden" name="update_settings" value="1">
                    
                    <?php 
                    $categoryNames = [
                        'general' => 'Geral',
                        'detection' => 'Detecção',
                        'rate_limit' => 'Rate Limiting',
                        'cloudflare' => 'Cloudflare'
                    ];
                    
                    foreach ($settingsByCategory as $category => $settings): 
                        $categoryName = $categoryNames[$category] ?? ucfirst($category);
                    ?>
                    <div class="glass rounded-2xl p-6 mb-6">
                        <h3 class="text-xl font-semibold text-white mb-6"><?php echo htmlspecialchars($categoryName); ?></h3>
                        
                        <div class="space-y-6">
                            <?php foreach ($settings as $setting): ?>
                                <?php if (!$setting['is_editable']): continue; endif; ?>
                                
                                <div class="flex items-start justify-between gap-4 pb-6 border-b border-white/5 last:border-0 last:pb-0">
                                    <div class="flex-1">
                                        <label class="block text-sm font-semibold text-white mb-2">
                                            <?php echo htmlspecialchars($setting['setting_key']); ?>
                                        </label>
                                        <?php if ($setting['description']): ?>
                                            <p class="text-xs text-zinc-400 mb-3"><?php echo htmlspecialchars($setting['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if ($setting['setting_type'] === 'boolean'): ?>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]" 
                                                    value="1"
                                                    <?php echo ($setting['setting_value'] == '1' || $setting['setting_value'] === '1') ? 'checked' : ''; ?>
                                                    class="sr-only peer"
                                                >
                                                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-white/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/20"></div>
                                                <span class="ml-3 text-sm text-zinc-400">
                                                    <?php echo ($setting['setting_value'] == '1') ? 'Ativado' : 'Desativado'; ?>
                                                </span>
                                            </label>
                                        <?php else: ?>
                                            <input 
                                                type="<?php echo $setting['setting_type'] === 'integer' ? 'number' : 'text'; ?>"
                                                name="settings[<?php echo htmlspecialchars($setting['setting_key']); ?>]"
                                                value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all"
                                                <?php if ($setting['setting_type'] === 'integer'): ?>
                                                    min="0"
                                                <?php endif; ?>
                                            >
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div class="flex justify-end gap-4">
                        <a href="dashboard.php" class="px-6 py-2.5 bg-white/10 text-white rounded-xl font-semibold hover:bg-white/20 transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2.5 btn-primary rounded-xl">
                            Salvar Configurações
                        </button>
                    </div>
                </form>
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

