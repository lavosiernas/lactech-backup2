<?php
/**
 * SafeNode - Configurações
 */

session_start();

// SEGURANÇA: Carregar helpers e aplicar headers
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

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

// Definição fixa das configurações que sempre devem existir
$defaultSettings = [
    'enabled' => [
        'setting_value' => '1',
        'setting_type' => 'boolean',
        'description'  => 'Habilitar/Desabilitar SafeNode',
        'category'     => 'general',
    ],
    'mode' => [
        'setting_value' => 'production',
        'setting_type'  => 'string',
        'description'   => 'Modo de operação: production, development, testing',
        'category'      => 'general',
    ],
    'log_retention_days' => [
        'setting_value' => '30',
        'setting_type'  => 'integer',
        'description'   => 'Dias para manter logs antes de arquivar',
        'category'      => 'general',
    ],
    'alert_email' => [
        'setting_value' => '',
        'setting_type'  => 'string',
        'description'   => 'Email para receber alertas críticos',
        'category'      => 'general',
    ],
    'alert_threshold' => [
        'setting_value' => '10',
        'setting_type'  => 'integer',
        'description'   => 'Número de ameaças por hora para enviar alerta',
        'category'      => 'general',
    ],
    'enable_whitelist' => [
        'setting_value' => '1',
        'setting_type'  => 'boolean',
        'description'   => 'Habilitar sistema de whitelist',
        'category'      => 'general',
    ],
    'enable_statistics' => [
        'setting_value' => '1',
        'setting_type'  => 'boolean',
        'description'   => 'Coletar estatísticas para dashboard',
        'category'      => 'general',
    ],
    'auto_block' => [
        'setting_value' => '1',
        'setting_type'  => 'boolean',
        'description'   => 'Bloquear IPs automaticamente quando detectar ameaças',
        'category'      => 'detection',
    ],
    'block_duration' => [
        'setting_value' => '3600',
        'setting_type'  => 'integer',
        'description'   => 'Duração do bloqueio em segundos (padrão: 1 hora)',
        'category'      => 'detection',
    ],
    'threat_score_threshold' => [
        'setting_value' => '70',
        'setting_type'  => 'integer',
        'description'   => 'Score mínimo para considerar ameaça crítica (0-100)',
        'category'      => 'detection',
    ],
    'login_max_attempts' => [
        'setting_value' => '5',
        'setting_type'  => 'integer',
        'description'   => 'Máximo de tentativas de login antes de bloquear',
        'category'      => 'rate_limit',
    ],
    'login_window' => [
        'setting_value' => '300',
        'setting_type'  => 'integer',
        'description'   => 'Janela de tempo para tentativas de login em segundos (5 minutos)',
        'category'      => 'rate_limit',
    ],
    'api_rate_limit' => [
        'setting_value' => '100',
        'setting_type'  => 'integer',
        'description'   => 'Limite de requisições por minuto para API',
        'category'      => 'rate_limit',
    ],
    'api_rate_window' => [
        'setting_value' => '60',
        'setting_type'  => 'integer',
        'description'   => 'Janela de tempo para rate limit da API em segundos',
        'category'      => 'rate_limit',
    ],
    'cloudflare_sync' => [
        'setting_value' => '1',
        'setting_type'  => 'boolean',
        'description'   => 'Sincronizar bloqueios com Cloudflare',
        'category'      => 'cloudflare',
    ],
    'cloudflare_zone_id' => [
        'setting_value' => '',
        'setting_type'  => 'string',
        'description'   => 'Zone ID do Cloudflare',
        'category'      => 'cloudflare',
    ],
    'cloudflare_api_token' => [
        'setting_value' => '',
        'setting_type'  => 'string',
        'description'   => 'API Token do Cloudflare',
        'category'      => 'cloudflare',
    ],
];

if ($db) {
    try {
        // Carregar o que já existe
        $stmt = $db->query("SELECT * FROM safenode_settings ORDER BY category, setting_key");
        $allSettings = $stmt->fetchAll();

        $settingsByKey = [];
        foreach ($allSettings as $setting) {
            $settings[$setting['category']][$setting['setting_key']] = $setting;
            $settingsByKey[$setting['setting_key']] = $setting;
        }

        // Garantir que todas as configs padrão existem (interface fixa)
        foreach ($defaultSettings as $key => $def) {
            if (!isset($settingsByKey[$key])) {
                $insert = $db->prepare("INSERT INTO safenode_settings (setting_key, setting_value, setting_type, description, category, is_editable) VALUES (?, ?, ?, ?, ?, 1)");
                $insert->execute([
                    $key,
                    $def['setting_value'],
                    $def['setting_type'],
                    $def['description'],
                    $def['category'],
                ]);

                $new = [
                    'id'           => $db->lastInsertId(),
                    'setting_key'  => $key,
                    'setting_value'=> $def['setting_value'],
                    'setting_type' => $def['setting_type'],
                    'description'  => $def['description'],
                    'category'     => $def['category'],
                    'is_editable'  => 1,
                ];

                $settings[$def['category']][$key] = $new;
            }
        }
    } catch (PDOException $e) {
        error_log("SafeNode Settings Error: " . $e->getMessage());
    }
}

$categories = ['general', 'rate_limit', 'detection', 'cloudflare'];

// Metadados para apresentação das categorias na interface
$categoryMeta = [
    'general' => [
        'label' => 'Geral',
        'description' => 'Comportamento global do SafeNode e alertas.',
        'icon' => 'settings',
        'color' => 'blue',
    ],
    'detection' => [
        'label' => 'Detecção',
        'description' => 'Como as ameaças são identificadas e bloqueadas.',
        'icon' => 'shield',
        'color' => 'red',
    ],
    'rate_limit' => [
        'label' => 'Rate Limit',
        'description' => 'Limites de requisição e proteção contra abuso.',
        'icon' => 'gauge',
        'color' => 'emerald',
    ],
    'cloudflare' => [
        'label' => 'Cloudflare',
        'description' => 'Integração e sincronização de bloqueios.',
        'icon' => 'cloud',
        'color' => 'amber',
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações | SafeNode</title>
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
                        <?php 
                            $meta = $categoryMeta[$category] ?? [
                                'label' => ucfirst($category),
                                'description' => '',
                                'icon' => 'settings',
                                'color' => 'blue',
                            ];
                            $color = $meta['color'];
                        ?>
                        <div class="glass-card rounded-2xl p-6 border border-<?php echo $color; ?>-500/10 hover:border-<?php echo $color; ?>-500/30 transition-all duration-300">
                            <div class="flex items-start justify-between mb-6">
                                <div>
                                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-<?php echo $color; ?>-500/10 border border-<?php echo $color; ?>-500/30 text-xs font-medium text-<?php echo $color; ?>-300 mb-3">
                                        <i data-lucide="<?php echo htmlspecialchars($meta['icon']); ?>" class="w-4 h-4"></i>
                                        <span><?php echo htmlspecialchars($meta['label']); ?></span>
                                    </div>
                                    <p class="text-xs text-zinc-500"><?php echo htmlspecialchars($meta['description']); ?></p>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <?php foreach ($settings[$category] as $key => $setting): ?>
                                    <?php if ($setting['is_editable']): ?>
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between gap-2">
                                                <label class="block text-sm font-semibold text-zinc-200">
                                                    <?php echo htmlspecialchars($setting['description']); ?>
                                                </label>
                                                <span class="text-[10px] px-2 py-0.5 rounded-full border border-zinc-700 text-zinc-400 font-mono uppercase">
                                                    <?php echo htmlspecialchars($setting['setting_type']); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                                <select name="<?php echo htmlspecialchars($key); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/60 text-white focus:ring-2 focus:ring-<?php echo $color; ?>-500 focus:border-<?php echo $color; ?>-500 transition-all text-sm">
                                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Habilitado</option>
                                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Desabilitado</option>
                                                </select>
                                            <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                                <input type="number" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/60 text-white focus:ring-2 focus:ring-<?php echo $color; ?>-500 focus:border-<?php echo $color; ?>-500 transition-all text-sm">
                                            <?php else: ?>
                                                <input type="text" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/60 text-white focus:ring-2 focus:ring-<?php echo $color; ?>-500 focus:border-<?php echo $color; ?>-500 transition-all text-sm">
                                            <?php endif; ?>
                                            
                                            <div class="flex items-center justify-between mt-1">
                                                <span class="text-[11px] text-zinc-500 font-mono truncate">
                                                    <?php echo htmlspecialchars($key); ?>
                                                </span>
                                                <?php if ($key === 'cloudflare_api_token' || $key === 'cloudflare_zone_id'): ?>
                                                    <span class="text-[10px] text-slate-500 font-medium">Recomendado via .env</span>
                                                <?php endif; ?>
                                            </div>
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
