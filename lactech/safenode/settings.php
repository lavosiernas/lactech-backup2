<?php
/**
 * SafeNode - Configurações
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();

$message = '';
$messageType = '';

// Salvar configurações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if ($db) {
        try {
            foreach ($_POST as $key => $value) {
                if ($key !== 'save_settings') {
                    $stmt = $db->prepare("UPDATE safenode_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$value, $key]);
                }
            }
            $message = "Configurações salvas com sucesso!";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Erro ao salvar configurações: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Buscar configurações
$settings = [];

if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM safenode_settings ORDER BY category, setting_key");
        $allSettings = $stmt->fetchAll();
        
        foreach ($allSettings as $setting) {
            $settings[$setting['category']][$setting['setting_key']] = $setting;
        }
    } catch (PDOException $e) {
        error_log("SafeNode Settings Error: " . $e->getMessage());
    }
}

$categories = ['general', 'rate_limit', 'detection', 'cloudflare'];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="shortcut icon" type="image/png" href="assets/img/logos (6).png">
    <link rel="apple-touch-icon" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'], mono: ['JetBrains Mono', 'monospace'] } } } }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }
        .glass-card { background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">Configurações</h2>
                <p class="text-xs text-zinc-400 mt-0.5">Gerencie as configurações do sistema</p>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'; ?> font-semibold">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <?php foreach ($categories as $category): ?>
                    <?php if (isset($settings[$category])): ?>
                        <div class="glass-card rounded-xl p-6">
                            <h3 class="text-lg font-bold text-white mb-6 capitalize tracking-tight">
                                <?php echo str_replace('_', ' ', $category); ?>
                            </h3>
                            
                            <div class="space-y-5">
                                <?php foreach ($settings[$category] as $key => $setting): ?>
                                    <?php if ($setting['is_editable']): ?>
                                        <div>
                                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                                <?php echo htmlspecialchars($setting['description']); ?>
                                            </label>
                                            
                                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                                <select name="<?php echo htmlspecialchars($key); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Habilitado</option>
                                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Desabilitado</option>
                                                </select>
                                            <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                                <input type="number" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                            <?php else: ?>
                                                <input type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                            <?php endif; ?>
                                            
                                            <?php if ($key === 'cloudflare_api_token' || $key === 'cloudflare_zone_id'): ?>
                                                <p class="mt-2 text-xs text-slate-500 font-medium">Configure no arquivo .env para maior segurança</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="flex justify-end gap-4">
                    <button type="submit" name="save_settings" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20">
                        Salvar Configurações
                    </button>
                </div>
            </form>
            </div>
        </div>
    </main>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
