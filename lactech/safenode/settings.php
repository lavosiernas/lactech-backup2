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
require_once __DIR__ . '/includes/ProtectionStreak.php';

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

// Buscar sequência de proteção ANTES de verificar atualização
$streakManager = new ProtectionStreak($db);
$protectionStreak = null;
if ($userId) {
    $protectionStreak = $streakManager->getStreak($userId, $currentSiteId);
}

// Verificar se houve atualização bem-sucedida (DEPOIS de buscar os dados atualizados)
if (isset($_GET['streak_updated'])) {
    // Recarregar dados após atualização para garantir que está sincronizado
    if ($userId) {
        $protectionStreak = $streakManager->getStreak($userId, $currentSiteId);
    }
    // Redirecionar sem mensagem para limpar a URL
    header("Location: settings.php");
    exit;
}

// Processar ativação/desativação da sequência
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_streak'])) {
    error_log("=== STREAK TOGGLE DEBUG ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("User ID: " . $userId);
    error_log("Site ID: " . $currentSiteId);
    
    if (!CSRFProtection::validate()) {
        error_log("CSRF validation FAILED");
        $message = "Token de segurança inválido.";
        $messageType = "error";
    } else {
        error_log("CSRF validation PASSED");
        
        // Verificar se o valor foi enviado (pode ser '1' ou '0' do hidden input)
        $streakEnabledValue = $_POST['streak_enabled'] ?? null;
        error_log("streak_enabled value from POST: " . var_export($streakEnabledValue, true));
        
        $enabled = ($streakEnabledValue === '1' || $streakEnabledValue === 1);
        error_log("Enabled (boolean): " . ($enabled ? 'true' : 'false'));
        
        if (!$userId) {
            error_log("ERROR: No user ID");
            $message = "Erro: Usuário não identificado. Faça login novamente.";
            $messageType = "error";
        } else {
            error_log("Calling setEnabled with: userId=$userId, siteId=$currentSiteId, enabled=" . ($enabled ? 'true' : 'false'));
            $result = $streakManager->setEnabled($userId, $currentSiteId, $enabled);
            error_log("setEnabled returned: " . ($result ? 'true' : 'false'));
            
            if ($result) {
                // Verificar se realmente foi salvo
                $checkStreak = $streakManager->getStreak($userId, $currentSiteId);
                error_log("After setEnabled, getStreak returned: " . print_r($checkStreak, true));
                
                // Redirecionar para evitar reenvio do formulário e atualizar estado
                error_log("Redirecting to settings.php");
                header("Location: settings.php?streak_updated=1");
                exit;
            } else {
                error_log("ERROR: setEnabled returned false");
                $errorMsg = "Erro ao atualizar sequência de proteção.";
                if (!$db) {
                    $errorMsg .= " Banco de dados não disponível.";
                }
                $message = $errorMsg;
                $messageType = "error";
            }
        }
    }
    error_log("=== END STREAK TOGGLE DEBUG ===");
}

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
                        <h2 class="text-2xl font-bold text-white tracking-tight">Configurações</h2>
                        <?php if ($selectedSite): ?>
                            <p class="text-sm text-zinc-500 font-mono mt-0.5"><?php echo htmlspecialchars($selectedSite['domain'] ?? ''); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-8">
                <?php if ($message && $messageType !== 'success'): ?>
                <div class="glass rounded-2xl p-4 mb-6 <?php echo $messageType === 'error' ? 'border-red-500/30 bg-red-500/10' : 'border-amber-500/30 bg-amber-500/10'; ?>">
                    <p class="text-white"><?php echo htmlspecialchars($message); ?></p>
                </div>
                <?php endif; ?>

                <!-- Sequência de Proteção -->
                <div class="glass rounded-2xl p-6 mb-6 border border-white/10" 
                     x-data="{ enabled: <?php echo ($protectionStreak && isset($protectionStreak['enabled']) && $protectionStreak['enabled']) ? 'true' : 'false'; ?> }"
                     x-on:streak-updated.window="enabled = $event.detail.enabled"
                     :class="enabled ? 'border-orange-500/30 bg-orange-500/5' : 'border-white/10'">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="bg-gradient-to-br from-orange-500 to-red-600 rounded-lg p-3 transition-opacity"
                             :class="enabled ? 'animate-pulse' : 'opacity-50'">
                            <i data-lucide="flame" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-semibold text-white">Sequência de Proteção</h3>
                            <p class="text-sm text-zinc-400">Acompanhe os dias consecutivos de proteção do seu site</p>
                        </div>
                        <div class="px-3 py-1 rounded-lg transition-colors"
                             :class="enabled ? 'bg-green-500/20 border border-green-500/30' : 'bg-zinc-500/20 border border-zinc-500/30'">
                            <span class="text-xs font-semibold" 
                                  :class="enabled ? 'text-green-400' : 'text-zinc-400'"
                                  x-text="enabled ? 'ATIVO' : 'INATIVO'"></span>
                        </div>
                    </div>
                    
                    <div x-show="enabled && <?php echo ($protectionStreak && isset($protectionStreak['current_streak']) && $protectionStreak['current_streak'] > 0) ? 'true' : 'false'; ?>" 
                         class="mb-6 p-5 bg-dark-800/50 rounded-xl border border-white/5"
                         style="display: <?php echo ($protectionStreak && isset($protectionStreak['enabled']) && $protectionStreak['enabled'] && isset($protectionStreak['current_streak']) && $protectionStreak['current_streak'] > 0) ? 'block' : 'none'; ?>;">
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <div class="text-xs text-zinc-500 mb-2 uppercase tracking-wider">Sequência Atual</div>
                                <div class="text-3xl font-bold text-orange-400">
                                    <?php echo $protectionStreak['current_streak'] ?? 0; ?> 
                                    <span class="text-lg text-zinc-500">dias</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-zinc-500 mb-2 uppercase tracking-wider">Recorde</div>
                                <div class="text-3xl font-bold text-zinc-300">
                                    <?php echo $protectionStreak['longest_streak'] ?? 0; ?> 
                                    <span class="text-lg text-zinc-500">dias</span>
                                </div>
                            </div>
                        </div>
                        <?php if ($protectionStreak && isset($protectionStreak['last_protected_date']) && $protectionStreak['last_protected_date']): ?>
                        <div class="mt-5 pt-5 border-t border-white/5">
                            <div class="text-xs text-zinc-500 mb-1">Última proteção registrada</div>
                            <div class="text-sm text-zinc-300 font-medium">
                                <?php echo date('d/m/Y', strtotime($protectionStreak['last_protected_date'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div id="streak-toggle-container" 
                         x-data="streakToggleData()"
                         x-on:streak-updated.window="enabled = $event.detail.enabled; currentStreak = $event.detail.current_streak || 0; longestStreak = $event.detail.longest_streak || 0">
                        <div class="flex items-center justify-between p-4 bg-dark-800/30 rounded-xl border border-white/5">
                            <div class="flex-1">
                                <div class="text-sm font-semibold text-white mb-1">
                                    <span x-text="enabled ? 'Desativar Sequência' : 'Ativar Sequência de Proteção'"></span>
                                </div>
                                <p class="text-xs text-zinc-400">
                                    <span x-show="enabled" x-text="'Clique para desativar o registro automático de dias consecutivos.'"></span>
                                    <span x-show="!enabled" x-text="'Quando ativado, o SafeNode registra automaticamente os dias consecutivos de proteção. Um badge aparecerá na sidebar mostrando sua sequência atual.'"></span>
                                </p>
                            </div>
                            <button 
                                type="button"
                                @click="toggle()"
                                :disabled="loading"
                                class="ml-4 px-6 py-3 rounded-xl font-semibold text-sm transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="enabled ? 'bg-red-500/20 hover:bg-red-500/30 text-red-400 border border-red-500/30' : 'bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-400 hover:to-red-500 text-white shadow-lg shadow-orange-500/20'"
                            >
                                <template x-if="loading">
                                    <span class="inline-flex items-center">
                                        <i data-lucide="loader-2" class="w-4 h-4 inline mr-2 animate-spin"></i>
                                        Processando...
                                    </span>
                                </template>
                                <template x-if="!loading && enabled">
                                    <span class="inline-flex items-center">
                                        <i data-lucide="x-circle" class="w-4 h-4 inline mr-2"></i>
                                        Desativar
                                    </span>
                                </template>
                                <template x-if="!loading && !enabled">
                                    <span class="inline-flex items-center">
                                        <i data-lucide="flame" class="w-4 h-4 inline mr-2"></i>
                                        Ativar Agora
                                    </span>
                                </template>
                            </button>
                        </div>
                        
                        <div x-show="enabled && currentStreak > 0" class="mt-4 p-4 bg-dark-800/50 rounded-xl border border-white/5" style="display: none;">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs text-zinc-500 mb-1">Sequência Atual</div>
                                    <div class="text-2xl font-bold text-orange-400">
                                        <span x-text="currentStreak"></span> <span class="text-sm text-zinc-500">dias</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="text-xs text-zinc-500 mb-1">Recorde</div>
                                    <div class="text-2xl font-bold text-zinc-300">
                                        <span x-text="longestStreak"></span> <span class="text-sm text-zinc-500">dias</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <script>
                    function streakToggleData() {
                        return {
                            enabled: <?php echo ($protectionStreak && isset($protectionStreak['enabled']) && $protectionStreak['enabled']) ? 'true' : 'false'; ?>,
                            loading: false,
                            currentStreak: <?php echo $protectionStreak['current_streak'] ?? 0; ?>,
                            longestStreak: <?php echo $protectionStreak['longest_streak'] ?? 0; ?>,
                            async toggle() {
                                console.log('=== TOGGLE STREAK START ===');
                                console.log('Current enabled state:', this.enabled);
                                
                                this.loading = true;
                                const newValue = !this.enabled;
                                console.log('New value to set:', newValue);
                                
                                try {
                                    const formData = new FormData();
                                    formData.append('enabled', newValue ? '1' : '0');
                                    
                                    console.log('Sending POST to api/toggle-streak.php');
                                    console.log('FormData enabled value:', newValue ? '1' : '0');
                                    
                                    const response = await fetch('api/toggle-streak.php', {
                                        method: 'POST',
                                        body: formData
                                    });
                                    
                                    console.log('Response status:', response.status);
                                    console.log('Response ok:', response.ok);
                                    
                                    const data = await response.json();
                                    console.log('Response data:', data);
                                    
                                    if (data.success) {
                                        console.log('SUCCESS! Updating local state...');
                                        console.log('Old enabled:', this.enabled);
                                        console.log('New enabled from API:', data.enabled);
                                        
                                        this.enabled = data.enabled;
                                        this.currentStreak = data.current_streak || 0;
                                        this.longestStreak = data.longest_streak || 0;
                                        
                                        console.log('State updated. New enabled:', this.enabled);
                                        console.log('Current streak:', this.currentStreak);
                                        console.log('Longest streak:', this.longestStreak);
                                        
                                        // Disparar evento para atualizar header do card
                                        window.dispatchEvent(new CustomEvent('streak-updated', { detail: data }));
                                        console.log('Event streak-updated dispatched');
                                        
                                        // Recriar ícones
                                        if (typeof lucide !== 'undefined') {
                                            setTimeout(() => {
                                                lucide.createIcons();
                                                console.log('Icons recreated');
                                            }, 100);
                                        }
                                    } else {
                                        console.error('API returned error:', data.error);
                                        alert('Erro: ' + (data.error || 'Erro ao atualizar sequência'));
                                    }
                                } catch (error) {
                                    console.error('Exception caught:', error);
                                    console.error('Error stack:', error.stack);
                                    alert('Erro ao atualizar sequência. Tente novamente.');
                                } finally {
                                    this.loading = false;
                                    console.log('=== TOGGLE STREAK END ===');
                                }
                            }
                        };
                    }
                    </script>
                </div>

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

