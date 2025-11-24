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
    'asaas_api_key' => [
        'setting_value' => '',
        'setting_type'  => 'string',
        'description'   => 'API Key da Asaas (Token de acesso)',
        'category'      => 'asaas',
    ],
    'asaas_sandbox' => [
        'setting_value' => '1',
        'setting_type'  => 'boolean',
        'description'   => 'Usar ambiente sandbox (testes)',
        'category'      => 'asaas',
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

$categories = ['general', 'rate_limit', 'detection', 'cloudflare', 'asaas'];

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
    'asaas' => [
        'label' => 'Asaas',
        'description' => 'Configurações de pagamento e integração com Asaas.',
        'icon' => 'credit-card',
        'color' => 'emerald',
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
        
        /* Glass Components Melhorados */
        .glass-card { 
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.5) 0%, rgba(24, 24, 27, 0.5) 100%); 
            backdrop-filter: blur(10px); 
            border: 1px solid rgba(255, 255, 255, 0.08); 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .glass-card:hover {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.7) 0%, rgba(24, 24, 27, 0.7) 100%);
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* Form Inputs Melhorados */
        .form-input {
            background: rgba(39, 39, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .form-input:focus {
            background: rgba(39, 39, 42, 0.8);
            border-color: rgba(59, 130, 246, 0.5);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1), 0 0 20px rgba(59, 130, 246, 0.1);
            outline: none;
        }

        /* Grid Pattern */
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* Badge Moderno */
        .modern-badge {
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        .modern-badge:hover {
            border-color: rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15);
        }

        /* Botões Melhorados */
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.3);
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            transform: translateY(-1px);
        }

        /* Category Card */
        .category-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 20px rgba(59, 130, 246, 0.1);
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        /* Depth Shadow */
        .depth-shadow {
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        /* Setting Item */
        .setting-item {
            transition: all 0.3s;
        }
        .setting-item:hover {
            background: rgba(255, 255, 255, 0.02);
            transform: translateX(4px);
        }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="hidden md:flex md:items-center md:gap-3">
                <div class="w-0.5 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
            <div>
                <h2 class="text-xl font-bold text-white tracking-tight">Configurações</h2>
                    <p class="text-xs text-zinc-400 mt-0.5 font-medium">Gerencie as configurações do sistema</p>
                </div>
            </div>
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white transition-colors" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-red-500/10 text-red-400 border border-red-500/30'; ?> font-semibold flex items-center gap-3 animate-fade-in shadow-lg">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-lg <?php echo $messageType === 'success' ? 'bg-emerald-500/20 border border-emerald-500/30' : 'bg-red-500/20 border border-red-500/30'; ?> flex items-center justify-center">
                            <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                        </div>
                    </div>
                    <p class="flex-1"><?php echo htmlspecialchars($message); ?></p>
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
                            // Mapeamento de classes de cor para Tailwind
                            $colorClasses = [
                                'blue' => ['bg' => 'bg-blue-500/15', 'border' => 'border-blue-500/30', 'text' => 'text-blue-400', 'hover' => 'hover:border-blue-500/30', 'bgDeco' => 'bg-blue-500/5'],
                                'red' => ['bg' => 'bg-red-500/15', 'border' => 'border-red-500/30', 'text' => 'text-red-400', 'hover' => 'hover:border-red-500/30', 'bgDeco' => 'bg-red-500/5'],
                                'emerald' => ['bg' => 'bg-emerald-500/15', 'border' => 'border-emerald-500/30', 'text' => 'text-emerald-400', 'hover' => 'hover:border-emerald-500/30', 'bgDeco' => 'bg-emerald-500/5'],
                                'amber' => ['bg' => 'bg-amber-500/15', 'border' => 'border-amber-500/30', 'text' => 'text-amber-400', 'hover' => 'hover:border-amber-500/30', 'bgDeco' => 'bg-amber-500/5'],
                            ];
                            $colorClass = $colorClasses[$color] ?? $colorClasses['blue'];
                        ?>
                        <!-- Category Card - Redesign -->
                        <div class="glass-card rounded-2xl p-6 category-card relative overflow-hidden animate-fade-in depth-shadow" style="animation-delay: <?php echo array_search($category, $categories) * 0.1; ?>s">
                            <!-- Grid pattern -->
                            <div class="absolute inset-0 grid-pattern opacity-20"></div>
                            
                            <!-- Decoração de fundo -->
                            <div class="absolute top-0 right-0 w-40 h-40 <?php echo $colorClass['bgDeco']; ?> rounded-full blur-3xl"></div>
                            
                            <div class="relative z-10">
                            <div class="flex items-start justify-between mb-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl <?php echo $colorClass['bg']; ?> border <?php echo $colorClass['border']; ?> flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="<?php echo htmlspecialchars($meta['icon']); ?>" class="w-6 h-6 <?php echo $colorClass['text']; ?>"></i>
                                        </div>
                                <div>
                                            <div class="flex items-center gap-2 mb-2">
                                                <h3 class="text-lg font-bold text-white"><?php echo htmlspecialchars($meta['label']); ?></h3>
                                                <span class="modern-badge inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg <?php echo $colorClass['bg']; ?> border <?php echo $colorClass['border']; ?> text-xs font-bold <?php echo $colorClass['text']; ?>">
                                                    <i data-lucide="<?php echo htmlspecialchars($meta['icon']); ?>" class="w-3.5 h-3.5"></i>
                                                    <?php echo count($settings[$category] ?? []); ?> config(s)
                                                </span>
                                    </div>
                                            <p class="text-sm text-zinc-400 font-medium"><?php echo htmlspecialchars($meta['description']); ?></p>
                                        </div>
                                </div>
                            </div>
                            
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($settings[$category] as $key => $setting): ?>
                                    <?php if ($setting['is_editable']): ?>
                                            <div class="setting-item p-4 rounded-xl bg-zinc-900/30 border border-white/5 <?php echo $colorClass['hover']; ?> transition-all">
                                                <div class="flex items-center justify-between gap-2 mb-3">
                                                    <label class="block text-sm font-bold text-zinc-200">
                                                    <?php echo htmlspecialchars($setting['description']); ?>
                                                </label>
                                                    <span class="modern-badge text-[10px] px-2.5 py-1 rounded-lg border <?php echo $colorClass['border']; ?> <?php echo $colorClass['bg']; ?> <?php echo $colorClass['text']; ?> font-mono uppercase font-bold">
                                                    <?php echo htmlspecialchars($setting['setting_type']); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                                    <select name="<?php echo htmlspecialchars($key); ?>" class="form-input w-full px-4 py-2.5 rounded-xl text-white text-sm font-medium">
                                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Habilitado</option>
                                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Desabilitado</option>
                                                </select>
                                            <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                                    <div class="relative">
                                                        <i data-lucide="hash" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                                                        <input type="number" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="form-input w-full pl-10 pr-4 py-2.5 rounded-xl text-white text-sm font-medium">
                                                    </div>
                                            <?php else: ?>
                                                    <div class="relative">
                                                        <i data-lucide="<?php echo ($key === 'cloudflare_api_token') ? 'lock' : 'edit-2'; ?>" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-500"></i>
                                                        <input <?php echo ($key === 'cloudflare_api_token') ? 'type="password" autocomplete="off"' : 'type="text"'; ?> name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" class="form-input w-full pl-10 pr-4 py-2.5 rounded-xl text-white text-sm font-medium">
                                                    </div>
                                            <?php endif; ?>
                                            
                                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-white/5">
                                                    <span class="text-[10px] text-zinc-500 font-mono truncate font-medium">
                                                        <i data-lucide="key" class="w-3 h-3 inline mr-1"></i>
                                                    <?php echo htmlspecialchars($key); ?>
                                                </span>
                                                <?php if ($key === 'cloudflare_api_token' || $key === 'cloudflare_zone_id'): ?>
                                                        <span class="text-[10px] text-amber-500 font-medium flex items-center gap-1">
                                                            <i data-lucide="info" class="w-3 h-3"></i>
                                                            Recomendado via .env
                                                        </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <!-- Botão Salvar - Redesign -->
                <div class="flex justify-end gap-4 pt-6 border-t border-white/5">
                    <button type="submit" name="save_settings" class="btn-primary px-8 py-3 text-white rounded-xl font-bold transition-all flex items-center gap-2 shadow-lg">
                        <i data-lucide="save" class="w-5 h-5"></i>
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
