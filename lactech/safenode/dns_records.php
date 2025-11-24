<?php
/**
 * SafeNode - Gerenciamento de DNS
 * Interface para configuração de registros DNS via Cloudflare
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/CloudflareAPI.php';

$db = getSafeNodeDatabase();
$siteId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$site = null;
$dnsRecords = [];
$error = '';
$success = '';

if ($siteId > 0 && $db) {
    // SEGURANÇA: Verificar que o site pertence ao usuário logado
    $userId = $_SESSION['safenode_user_id'] ?? null;
    $stmt = $db->prepare("SELECT * FROM safenode_sites WHERE id = ? AND user_id = ?");
    $stmt->execute([$siteId, $userId]);
    $site = $stmt->fetch();
}

if (!$site) {
    header('Location: sites.php');
    exit;
}

$cf = new CloudflareAPI($site['cloudflare_api_token'] ?? null); // Assuming token might be per site or global
// Fallback to global token if not in site (which is likely the case in this system)
if (empty($site['cloudflare_api_token'])) {
    // Check settings or env
    // In this system, settings seems to be in safenode_settings table
    $stmt = $db->query("SELECT setting_value FROM safenode_settings WHERE setting_key = 'cloudflare_api_token'");
    $tokenSetting = $stmt->fetch();
    $token = $tokenSetting ? $tokenSetting['setting_value'] : null;
    $cf = new CloudflareAPI($token);
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_record'])) {
        $type = $_POST['type'];
        $name = $_POST['name'];
        $content = $_POST['content'];
        $ttl = intval($_POST['ttl']);
        $proxied = isset($_POST['proxied']);

        $result = $cf->createDNSRecord($site['cloudflare_zone_id'], $type, $name, $content, $ttl, $proxied);
        if ($result['success']) {
            $success = 'Registro DNS criado com sucesso!';
        } else {
            $error = 'Erro ao criar registro: ' . json_encode($result['error']);
        }
    } elseif (isset($_POST['delete_record'])) {
        $recordId = $_POST['record_id'];
        $result = $cf->deleteDNSRecord($site['cloudflare_zone_id'], $recordId);
        if ($result['success']) {
            $success = 'Registro removido com sucesso!';
        } else {
            $error = 'Erro ao remover registro: ' . json_encode($result['error']);
        }
    } elseif (isset($_POST['update_record'])) {
        // Update logic here if needed
    }
}

// Verificar se API Token e Zone ID estão configurados
$hasApiToken = false;
if ($site['cloudflare_api_token']) {
    $hasApiToken = true;
} else {
    $stmt = $db->query("SELECT setting_value FROM safenode_settings WHERE setting_key = 'cloudflare_api_token'");
    $tokenSetting = $stmt->fetch();
    if ($tokenSetting && !empty($tokenSetting['setting_value'])) {
        $hasApiToken = true;
        $cf = new CloudflareAPI($tokenSetting['setting_value']);
    }
}

// Fetch Records
if (!$site['cloudflare_zone_id']) {
    $error = 'Este site não está conectado ao Cloudflare (Zone ID ausente). Configure o Zone ID nas configurações do site.';
} elseif (!$hasApiToken) {
    $error = 'API Token do Cloudflare não configurado. Configure o API Token em Configurações → Cloudflare → API Token.';
} else {
    $recordsResult = $cf->listDNSRecords($site['cloudflare_zone_id']);
    if ($recordsResult['success']) {
        $dnsRecords = $recordsResult['data']['result'] ?? [];
    } else {
        $errorMsg = is_array($recordsResult['error']) ? json_encode($recordsResult['error']) : $recordsResult['error'];
        $error = 'Erro ao buscar registros DNS: ' . $errorMsg;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNS Config - <?php echo htmlspecialchars($site['domain']); ?></title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
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

        /* Tabela Melhorada */
        .table-row {
            transition: all 0.2s;
        }
        .table-row:hover {
            background: rgba(255, 255, 255, 0.03) !important;
            transform: translateX(2px);
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
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white transition-colors" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
            <div class="hidden md:flex md:items-center md:gap-3">
                <a href="sites.php" class="p-2 text-zinc-400 hover:text-white hover:bg-white/5 rounded-lg transition-all">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                <div class="w-0.5 h-6 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                <div>
                    <div class="flex items-center gap-2">
                    <h2 class="text-xl font-bold text-white tracking-tight">Configuração DNS</h2>
                        <div class="p-1.5 bg-blue-500/15 rounded-lg border border-blue-500/25">
                            <i data-lucide="server" class="w-4 h-4 text-blue-400"></i>
                        </div>
                    </div>
                    <p class="text-xs text-zinc-400 mt-0.5 font-medium">Gerenciando <?php echo htmlspecialchars($site['domain']); ?></p>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-6xl mx-auto space-y-6">
                <?php if ($error): ?>
                    <div class="p-4 rounded-xl bg-red-500/10 text-red-400 border border-red-500/30 flex items-center gap-3 animate-fade-in shadow-lg">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-red-500/20 border border-red-500/30 flex items-center justify-center">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <p class="flex-1 font-semibold"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="p-4 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center gap-3 animate-fade-in shadow-lg">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-lg bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                            </div>
                        </div>
                        <p class="flex-1 font-semibold"><?php echo htmlspecialchars($success); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Add Record Form - Redesign -->
                <div class="glass-card rounded-xl p-6 relative overflow-hidden animate-fade-in">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-30"></div>
                    
                    <!-- Decoração de fundo -->
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center">
                                <i data-lucide="plus-circle" class="w-5 h-5 text-blue-400"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-white">Adicionar Registro DNS</h3>
                                <p class="text-xs text-zinc-400 mt-0.5 font-medium">Crie novos registros DNS para seu domínio</p>
                            </div>
                        </div>
                        
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <input type="hidden" name="add_record" value="1">
                        
                        <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-zinc-300 mb-2">Tipo</label>
                                <select name="type" class="form-input w-full px-3 py-2.5 rounded-xl text-white text-sm font-medium">
                                <option value="A">A</option>
                                <option value="AAAA">AAAA</option>
                                <option value="CNAME">CNAME</option>
                                <option value="TXT">TXT</option>
                                <option value="MX">MX</option>
                                <option value="NS">NS</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-zinc-300 mb-2">Nome</label>
                                <input type="text" name="name" placeholder="@ ou subdominio" required class="form-input w-full px-3 py-2.5 rounded-xl text-white text-sm font-medium">
                        </div>
                        
                        <div class="md:col-span-4">
                                <label class="block text-xs font-bold text-zinc-300 mb-2">Conteúdo</label>
                                <input type="text" name="content" placeholder="192.0.2.1" required class="form-input w-full px-3 py-2.5 rounded-xl text-white text-sm font-medium">
                                <p class="text-[10px] text-zinc-500 mt-1 font-medium">IPv4, domínio, texto...</p>
                        </div>

                        <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-zinc-300 mb-2">Proxy (CDN)</label>
                                <label class="flex items-center gap-2.5 cursor-pointer p-3 rounded-xl bg-zinc-900/40 border border-white/5 hover:border-orange-500/30 transition-all">
                                    <input type="checkbox" name="proxied" checked class="w-5 h-5 rounded bg-zinc-800 border-zinc-600 text-orange-500 focus:ring-orange-500">
                                    <span class="text-sm text-zinc-300 font-semibold">Proxied</span>
                                    <i data-lucide="cloud" class="w-4 h-4 text-orange-400"></i>
                            </label>
                        </div>
                        
                        <div class="md:col-span-1">
                             <input type="hidden" name="ttl" value="1"> <!-- Auto -->
                                <button type="submit" class="btn-primary w-full px-4 py-2.5 text-white rounded-xl text-sm font-bold transition-all flex items-center justify-center gap-2">
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                Adicionar
                            </button>
                        </div>
                    </form>
                </div>
                </div>

                <!-- Records List - Redesign -->
                <div class="glass-card rounded-xl overflow-hidden relative animate-fade-in depth-shadow">
                    <!-- Grid pattern -->
                    <div class="absolute inset-0 grid-pattern opacity-20"></div>
                    
                    <div class="relative z-10">
                        <div class="p-6 border-b border-white/5 bg-zinc-900/30">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center">
                                        <i data-lucide="layout-list" class="w-5 h-5 text-purple-400"></i>
                                    </div>
                                    <div>
                        <h3 class="text-lg font-bold text-white">Registros DNS</h3>
                                        <p class="text-xs text-zinc-400 mt-0.5 font-medium"><?php echo count($dnsRecords); ?> registro(s) encontrado(s)</p>
                                    </div>
                                </div>
                            </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                                <thead class="bg-zinc-900/50 text-zinc-400 font-bold uppercase text-xs tracking-wider border-b border-white/5">
                                <tr>
                                    <th class="px-6 py-4">Tipo</th>
                                    <th class="px-6 py-4">Nome</th>
                                    <th class="px-6 py-4">Conteúdo</th>
                                    <th class="px-6 py-4">Proxy Status</th>
                                    <th class="px-6 py-4">TTL</th>
                                    <th class="px-6 py-4 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($dnsRecords)): ?>
                                    <tr>
                                            <td colspan="6" class="px-6 py-12 text-center">
                                                <div class="flex flex-col items-center gap-3">
                                                    <div class="w-16 h-16 rounded-xl bg-zinc-900/60 border border-white/5 flex items-center justify-center">
                                                        <i data-lucide="database" class="w-8 h-8 text-zinc-500"></i>
                                                    </div>
                                                    <p class="text-zinc-500 font-semibold">Nenhum registro encontrado</p>
                                                    <p class="text-xs text-zinc-600 font-medium">Use o formulário acima para adicionar registros DNS</p>
                                                </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dnsRecords as $record): ?>
                                            <tr class="table-row group">
                                            <td class="px-6 py-4">
                                                    <span class="modern-badge font-black w-14 inline-block text-center text-xs py-1.5 rounded-lg <?php 
                                                        $typeColors = [
                                                            'A' => 'bg-blue-500/15 text-blue-400 border-blue-500/30',
                                                            'AAAA' => 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
                                                            'CNAME' => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
                                                            'TXT' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
                                                            'MX' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
                                                            'NS' => 'bg-red-500/15 text-red-400 border-red-500/30'
                                                        ];
                                                        echo $typeColors[$record['type']] ?? 'bg-zinc-800 text-zinc-300 border-white/5';
                                                    ?> border">
                                                    <?php echo htmlspecialchars($record['type']); ?>
                                                </span>
                                            </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2">
                                                        <i data-lucide="globe" class="w-4 h-4 text-zinc-500"></i>
                                                        <span class="font-mono text-zinc-200 font-semibold"><?php echo htmlspecialchars($record['name']); ?></span>
                                                    </div>
                                            </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex items-center gap-2 max-w-xs">
                                                        <i data-lucide="link" class="w-4 h-4 text-zinc-500 flex-shrink-0"></i>
                                                        <span class="font-mono text-zinc-400 text-xs truncate font-medium" title="<?php echo htmlspecialchars($record['content']); ?>">
                                                <?php echo htmlspecialchars($record['content']); ?>
                                                        </span>
                                                    </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($record['proxied']): ?>
                                                        <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-orange-500/15 text-orange-400 border-orange-500/30">
                                                            <i data-lucide="cloud" class="w-3.5 h-3.5"></i> Proxied
                                                    </span>
                                                <?php else: ?>
                                                        <span class="modern-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-zinc-800/60 text-zinc-400 border-white/10">
                                                            <i data-lucide="cloud-x" class="w-3.5 h-3.5"></i> DNS Only
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-zinc-900/60 text-zinc-400 border border-white/5">
                                                        <i data-lucide="clock" class="w-3 h-3"></i>
                                                <?php echo $record['ttl'] == 1 ? 'Auto' : $record['ttl'] . 's'; ?>
                                                    </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                    <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este registro?');" class="inline">
                                                    <input type="hidden" name="delete_record" value="1">
                                                    <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($record['id']); ?>">
                                                        <button type="submit" class="p-2.5 text-zinc-400 hover:text-red-400 hover:bg-red-500/15 rounded-xl border border-transparent hover:border-red-500/30 transition-all group/btn">
                                                            <i data-lucide="trash-2" class="w-4 h-4 group-hover/btn:scale-110 transition-transform"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>

