<?php
/**
 * SafeNode - Gerenciamento de reCAPTCHA
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
require_once __DIR__ . '/includes/SafeNodeReCAPTCHA.php';
require_once __DIR__ . '/includes/Settings.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Processar atualização de configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && CSRFProtection::validate()) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update') {
        try {
            $settings = [
                'safenode_recaptcha_version' => in_array($_POST['version'] ?? 'v2', ['v2', 'v3']) ? $_POST['version'] : 'v2',
                'safenode_recaptcha_action' => trim($_POST['action_name'] ?? 'submit'),
                'safenode_recaptcha_score_threshold' => (float)($_POST['score_threshold'] ?? 0.5),
                'safenode_recaptcha_enabled' => isset($_POST['enabled']) ? '1' : '0'
            ];
            
            // Validar score threshold
            $settings['safenode_recaptcha_score_threshold'] = max(0.0, min(1.0, $settings['safenode_recaptcha_score_threshold']));
            
            if ($db) {
                $updated = 0;
                foreach ($settings as $key => $value) {
                    $stmt = $db->prepare("UPDATE safenode_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                    if ($stmt->rowCount() > 0) {
                        $updated++;
                    } else {
                        // Se não existe, criar
                        $stmt = $db->prepare("INSERT INTO safenode_settings (setting_key, setting_value, setting_type, category, description, is_editable) VALUES (?, ?, 'string', 'security', 'SafeNode reCAPTCHA Setting', 1)");
                        try {
                            $stmt->execute([$key, $value]);
                            $updated++;
                        } catch (PDOException $e) {
                            // Ignorar se já existe
                        }
                    }
                }
                
                if ($updated > 0) {
                    $message = 'Configurações do reCAPTCHA SafeNode atualizadas com sucesso!';
                    $messageType = 'success';
                    
                    // Recarregar configurações
                    SafeNodeReCAPTCHA::init();
                } else {
                    $message = 'Nenhuma configuração foi atualizada.';
                    $messageType = 'warning';
                }
            }
        } catch (Exception $e) {
            error_log("SafeNode reCAPTCHA Update Error: " . $e->getMessage());
            $message = 'Erro ao atualizar configurações.';
            $messageType = 'error';
        }
    }
}

// Buscar configurações atuais
SafeNodeReCAPTCHA::init();
$currentVersion = SafeNodeSettings::get('safenode_recaptcha_version', 'v2');
$currentAction = SafeNodeSettings::get('safenode_recaptcha_action', 'submit');
$currentScoreThreshold = (float) SafeNodeSettings::get('safenode_recaptcha_score_threshold', '0.5');
$currentEnabled = SafeNodeSettings::get('safenode_recaptcha_enabled', '0') === '1';
$isConfigured = SafeNodeReCAPTCHA::isEnabled();

// Obter URL base
$baseUrl = getSafeNodeBaseUrl();

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>reCAPTCHA | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
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
        
        .code-block {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            overflow-x: auto;
            position: relative;
        }
        
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .code-block:hover .copy-btn {
            opacity: 1;
        }
        
        [x-cloak] { display: none !important; }
        
        /* Select/Dropdown styles */
        select {
            background-color: #0a0a0a;
            color: #ffffff;
        }
        
        select option {
            background-color: #0a0a0a;
            color: #ffffff;
            padding: 8px;
        }
        
        select option:hover,
        select option:checked {
            background-color: #1a1a1a;
            color: #ffffff;
        }
    </style>
</head>
<body class="h-full" x-data="{ sidebarOpen: false }">
    <div class="flex h-full">
        <!-- Sidebar Desktop -->
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
                    <a href="recaptcha.php" class="nav-item active">
                        <i data-lucide="shield" class="w-5 h-5"></i>
                        <span class="font-medium">reCAPTCHA</span>
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
                    <a href="recaptcha.php" class="nav-item active" @click="sidebarOpen = false">
                        <i data-lucide="shield" class="w-5 h-5 flex-shrink-0"></i>
                        <span class="font-medium whitespace-nowrap">reCAPTCHA</span>
                    </a>
                    <a href="settings.php" class="nav-item" @click="sidebarOpen = false">
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
            <header class="h-20 bg-dark-900/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-6">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <div>
                        <h2 class="text-2xl font-bold text-white tracking-tight">reCAPTCHA</h2>
                        <p class="text-sm text-zinc-500 font-mono mt-0.5">SafeNode reCAPTCHA (100% Próprio)</p>
                    </div>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-8">
                <?php if ($message): ?>
                <div class="glass rounded-2xl p-4 mb-6 <?php echo $messageType === 'success' ? 'border-green-500/30 bg-green-500/10' : ($messageType === 'error' ? 'border-red-500/30 bg-red-500/10' : 'border-amber-500/30 bg-amber-500/10'); ?>">
                    <p class="text-white"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <?php endif; ?>

                <!-- Status Card -->
                <div class="glass rounded-2xl p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-white">Serviço Gerenciado de reCAPTCHA</h3>
                        <div class="flex items-center gap-2">
                            <?php if ($isConfigured): ?>
                                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                                <span class="text-sm text-green-400 font-medium">Ativo</span>
                            <?php else: ?>
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                <span class="text-sm text-red-400 font-medium">Não Configurado</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <p class="text-sm text-zinc-400">
                            <strong class="text-white">100% SafeNode:</strong> Sistema próprio de verificação humana, 
                            sem dependência de serviços externos. Usa análise comportamental e ML do SafeNode.
                        </p>
                        <p class="text-sm text-zinc-400">
                            <?php if ($isConfigured): ?>
                                ✅ O serviço está ativo. Clientes só precisam da API Key do SafeNode (mesma da Verificação Humana).
                            <?php else: ?>
                                Ative o reCAPTCHA SafeNode abaixo. Seus clientes não precisam configurar nada - 
                                eles só usam a API Key do SafeNode! Sistema totalmente gerenciado pelo SafeNode.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Configuração Form -->
                <form method="POST" action="recaptcha.php" class="glass rounded-2xl p-6 mb-6">
                    <?php echo CSRFProtection::getTokenField(); ?>
                    <input type="hidden" name="action" value="update">
                    
                    <h3 class="text-lg font-semibold text-white mb-6">Configurações do reCAPTCHA</h3>
                    
                    <div class="space-y-6" x-data="{ version: '<?php echo $currentVersion; ?>' }">
                        <!-- Enabled Toggle -->
                        <div class="flex items-center justify-between pb-6 border-b border-white/5">
                            <div>
                                <label class="block text-sm font-semibold text-white mb-2">Habilitar reCAPTCHA</label>
                                <p class="text-xs text-zinc-400">Ative o reCAPTCHA para usar em formulários e APIs</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="enabled" value="1" <?php echo $currentEnabled ? 'checked' : ''; ?> class="sr-only peer">
                                <div class="w-11 h-6 bg-white/10 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-white/20 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-white/20"></div>
                            </label>
                        </div>

                        <!-- Version -->
                        <div class="pb-6 border-b border-white/5">
                            <label class="block text-sm font-semibold text-white mb-2">
                                Versão
                            </label>
                            <p class="text-xs text-zinc-400 mb-3">
                                Escolha entre v2 (checkbox visível) ou v3 (invisível, score-based)
                            </p>
                            <select name="version" x-model="version" class="w-full bg-dark-800 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all [&>option]:bg-dark-900 [&>option]:text-white">
                                <option value="v2" <?php echo $currentVersion === 'v2' ? 'selected' : ''; ?>>v2 - Checkbox (Visível)</option>
                                <option value="v3" <?php echo $currentVersion === 'v3' ? 'selected' : ''; ?>>v3 - Score (Invisível)</option>
                            </select>
                        </div>

                        <!-- Action (v3) -->
                        <div class="pb-6 border-b border-white/5" x-show="version === 'v3'" x-cloak>
                            <label class="block text-sm font-semibold text-white mb-2">
                                Nome da Ação (v3)
                            </label>
                            <p class="text-xs text-zinc-400 mb-3">
                                Nome da ação para identificar onde o reCAPTCHA v3 é usado (ex: login, register, submit)
                            </p>
                            <input 
                                type="text" 
                                name="action_name" 
                                value="<?php echo htmlspecialchars($currentAction); ?>"
                                placeholder="submit"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all"
                            >
                        </div>

                        <!-- Score Threshold (v3) -->
                        <div class="pb-6 border-b border-white/5" x-show="version === 'v3'" x-cloak>
                            <label class="block text-sm font-semibold text-white mb-2">
                                Score Threshold (v3)
                            </label>
                            <p class="text-xs text-zinc-400 mb-3">
                                Score mínimo para aprovar (0.0 = bot, 1.0 = humano). Recomendado: 0.5
                            </p>
                            <input 
                                type="number" 
                                name="score_threshold" 
                                value="<?php echo $currentScoreThreshold; ?>"
                                min="0" 
                                max="1" 
                                step="0.1"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 transition-all"
                            >
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 mt-6">
                        <a href="dashboard.php" class="px-6 py-2.5 bg-white/10 text-white rounded-xl font-semibold hover:bg-white/20 transition-colors">
                            Cancelar
                        </a>
                        <button type="submit" class="px-6 py-2.5 btn-primary rounded-xl">
                            Salvar Configurações
                        </button>
                    </div>
                </form>

                <!-- Documentação -->
                <?php if ($isConfigured): ?>
                <div class="glass rounded-2xl p-6">
                    <h3 class="text-lg font-semibold text-white mb-4">Como Integrar no Site do Cliente</h3>
                    <div class="space-y-6 text-sm text-zinc-400">
                        <div>
                            <p class="text-white font-semibold mb-2">1. Inclua o script no HTML:</p>
                            <div class="code-block relative mt-2">
                                <button onclick="copyCode(this)" class="copy-btn bg-white/10 hover:bg-white/20 text-white px-3 py-1 rounded text-xs transition-opacity">
                                    Copiar
                                </button>
                                <code>&lt;script src="<?php echo $baseUrl; ?>/api/sdk/safenode-recaptcha-script.js"
        data-api-key="sk_SUA_API_KEY_AQUI"
        data-api-url="<?php echo $baseUrl; ?>/api/sdk"
        data-version="<?php echo $currentVersion; ?>"&gt;&lt;/script&gt;</code>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-white font-semibold mb-2">2. Para v2 (Checkbox visível):</p>
                            <div class="code-block relative mt-2">
                                <button onclick="copyCode(this)" class="copy-btn bg-white/10 hover:bg-white/20 text-white px-3 py-1 rounded text-xs transition-opacity">
                                    Copiar
                                </button>
                                <code>&lt;div id="recaptcha-widget"&gt;&lt;/div&gt;

&lt;script&gt;
SafeNodeReCAPTCHA.init({
    apiKey: 'sk_SUA_API_KEY_AQUI',
    apiUrl: '<?php echo $baseUrl; ?>/api/sdk',
    version: 'v2'
}).render('recaptcha-widget', function(result) {
    if (result.success) {
        // Token está em input[name="safenode-recaptcha-token"]
        console.log('reCAPTCHA resolvido!');
    }
});
&lt;/script&gt;</code>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-white font-semibold mb-2">3. Para v3 (Invisível):</p>
                            <div class="code-block relative mt-2">
                                <button onclick="copyCode(this)" class="copy-btn bg-white/10 hover:bg-white/20 text-white px-3 py-1 rounded text-xs transition-opacity">
                                    Copiar
                                </button>
                                <code>&lt;script&gt;
SafeNodeReCAPTCHA.init({
    apiKey: 'sk_SUA_API_KEY_AQUI',
    apiUrl: '<?php echo $baseUrl; ?>/api/sdk',
    version: 'v3'
}).execute().then(function(result) {
    if (result.success) {
        // Token está em input[name="safenode-recaptcha-token"]
        console.log('Score:', result.score);
    }
});
&lt;/script&gt;</code>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-white font-semibold mb-2">4. Validar no Backend (PHP):</p>
                            <div class="code-block relative mt-2">
                                <button onclick="copyCode(this)" class="copy-btn bg-white/10 hover:bg-white/20 text-white px-3 py-1 rounded text-xs transition-opacity">
                                    Copiar
                                </button>
                                <code>$token = $_POST['safenode-recaptcha-token'] ?? '';
$apiKey = 'sk_SUA_API_KEY_AQUI';

$response = file_get_contents('<?php echo $baseUrl; ?>/api/sdk/safenode-recaptcha-validate.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => ['Content-Type: application/json', 'X-API-Key: ' . $apiKey],
        'content' => json_encode(['response' => $token, 'api_key' => $apiKey])
    ]
]));

$result = json_decode($response, true);
if ($result['success']) {
    // reCAPTCHA válido! Processar formulário
}</code>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-white/5">
                            <p class="text-amber-400"><strong>⚠️ Importante:</strong> O cliente precisa ter uma API Key do SafeNode. Use a mesma API Key da Verificação Humana!</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        lucide.createIcons();
        
        // Função para copiar código
        function copyCode(btn) {
            const code = btn.nextElementSibling || btn.parentElement.querySelector('code');
            const text = code.textContent || code.innerText;
            
            navigator.clipboard.writeText(text).then(function() {
                const originalText = btn.textContent;
                btn.textContent = 'Copiado!';
                btn.classList.add('bg-green-500/20');
                
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.classList.remove('bg-green-500/20');
                }, 2000);
            });
        }
        
    </script>
</body>
</html>

