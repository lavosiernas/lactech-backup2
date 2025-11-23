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
        .glass-card { background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%); backdrop-filter: blur(8px); border: 1px solid rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
            <div class="hidden md:block">
                <div class="flex items-center gap-2">
                    <a href="sites.php" class="text-zinc-400 hover:text-white transition-colors">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <h2 class="text-xl font-bold text-white tracking-tight">Configuração DNS</h2>
                </div>
                <p class="text-xs text-zinc-400 mt-0.5 ml-7">Gerenciando <?php echo htmlspecialchars($site['domain']); ?></p>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-6xl mx-auto space-y-6">
                <?php if ($error): ?>
                    <div class="p-4 rounded-xl bg-red-500/10 text-red-400 border border-red-500/20 flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="p-4 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-5 h-5"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Add Record Form -->
                <div class="glass-card rounded-xl p-6">
                    <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="plus" class="w-5 h-5 text-blue-400"></i>
                        Adicionar Registro
                    </h3>
                    <form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                        <input type="hidden" name="add_record" value="1">
                        
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-zinc-400 mb-1">Tipo</label>
                            <select name="type" class="w-full px-3 py-2 rounded-lg bg-zinc-900/50 border border-white/10 text-white text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="A">A</option>
                                <option value="AAAA">AAAA</option>
                                <option value="CNAME">CNAME</option>
                                <option value="TXT">TXT</option>
                                <option value="MX">MX</option>
                                <option value="NS">NS</option>
                            </select>
                        </div>
                        
                        <div class="md:col-span-3">
                            <label class="block text-xs font-semibold text-zinc-400 mb-1">Nome</label>
                            <input type="text" name="name" placeholder="@ ou subdominio" required class="w-full px-3 py-2 rounded-lg bg-zinc-900/50 border border-white/10 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="md:col-span-4">
                            <label class="block text-xs font-semibold text-zinc-400 mb-1">Conteúdo (IPv4, domínio, texto...)</label>
                            <input type="text" name="content" placeholder="192.0.2.1" required class="w-full px-3 py-2 rounded-lg bg-zinc-900/50 border border-white/10 text-white text-sm focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-zinc-400 mb-1">Proxy (CDN)</label>
                            <label class="flex items-center gap-2 cursor-pointer mt-2">
                                <input type="checkbox" name="proxied" checked class="w-4 h-4 rounded bg-zinc-800 border-zinc-600 text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-zinc-300">Proxied</span>
                            </label>
                        </div>
                        
                        <div class="md:col-span-1">
                             <input type="hidden" name="ttl" value="1"> <!-- Auto -->
                            <button type="submit" class="w-full px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
                                Adicionar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Records List -->
                <div class="glass-card rounded-xl overflow-hidden">
                    <div class="p-6 border-b border-white/5">
                        <h3 class="text-lg font-bold text-white">Registros DNS</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs tracking-wider">
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
                                        <td colspan="6" class="px-6 py-8 text-center text-zinc-500">
                                            Nenhum registro encontrado ou erro ao carregar.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dnsRecords as $record): ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors group">
                                            <td class="px-6 py-4">
                                                <span class="font-bold w-12 inline-block text-center text-xs py-1 rounded bg-zinc-800 text-zinc-300 border border-white/5">
                                                    <?php echo htmlspecialchars($record['type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-zinc-300">
                                                <?php echo htmlspecialchars($record['name']); ?>
                                            </td>
                                            <td class="px-6 py-4 font-mono text-zinc-400 max-w-xs truncate" title="<?php echo htmlspecialchars($record['content']); ?>">
                                                <?php echo htmlspecialchars($record['content']); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($record['proxied']): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium bg-orange-500/10 text-orange-400 border border-orange-500/20">
                                                        <i data-lucide="cloud" class="w-3 h-3"></i> Proxied
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium bg-zinc-800 text-zinc-400 border border-white/5">
                                                        <i data-lucide="cloud-off" class="w-3 h-3"></i> DNS Only
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-zinc-500 text-xs">
                                                <?php echo $record['ttl'] == 1 ? 'Auto' : $record['ttl'] . 's'; ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <form method="POST" onsubmit="return confirm('Tem certeza que deseja excluir este registro?');">
                                                    <input type="hidden" name="delete_record" value="1">
                                                    <input type="hidden" name="record_id" value="<?php echo htmlspecialchars($record['id']); ?>">
                                                    <button type="submit" class="p-2 text-zinc-400 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors">
                                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
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
    </main>
    <script>lucide.createIcons();</script>
</body>
</html>

