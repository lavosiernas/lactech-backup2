<?php
/**
 * SafeNode - Gerenciamento de Sites
 * Interface similar ao Cloudflare para configurar sites protegidos
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

// Mensagens de sessão
$message = $_SESSION['safenode_message'] ?? '';
$messageType = $_SESSION['safenode_message_type'] ?? '';
unset($_SESSION['safenode_message']);
unset($_SESSION['safenode_message_type']);

// Criar tabela se não existir
if ($db) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS safenode_sites (
            id INT PRIMARY KEY AUTO_INCREMENT,
            domain VARCHAR(255) NOT NULL,
            display_name VARCHAR(255) NULL,
            cloudflare_zone_id VARCHAR(100) NULL,
            cloudflare_status VARCHAR(50) DEFAULT 'active',
            ssl_status VARCHAR(50) DEFAULT 'pending',
            security_level VARCHAR(50) DEFAULT 'medium',
            auto_block BOOLEAN DEFAULT TRUE,
            rate_limit_enabled BOOLEAN DEFAULT TRUE,
            threat_detection_enabled BOOLEAN DEFAULT TRUE,
            is_active BOOLEAN DEFAULT TRUE,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_domain (domain),
            INDEX idx_active (is_active),
            UNIQUE KEY unique_domain (domain)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (PDOException $e) {
        error_log("SafeNode Sites Table Error: " . $e->getMessage());
    }
}

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_site'])) {
        $domain = trim($_POST['domain'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $cloudflareZoneId = trim($_POST['cloudflare_zone_id'] ?? '');
        $securityLevel = $_POST['security_level'] ?? 'medium';
        
        // Validação de domínio
        $domain = preg_replace('/^https?:\/\//', '', $domain);
        $domain = preg_replace('/^www\./', '', $domain);
        $domain = rtrim($domain, '/');
        
        if (!empty($domain) && $db) {
            // Validação básica de formato de domínio
            if (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain)) {
                $message = "Formato de domínio inválido!";
                $messageType = "error";
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO safenode_sites (domain, display_name, cloudflare_zone_id, security_level) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$domain, $displayName ?: null, $cloudflareZoneId ?: null, $securityLevel]);
                    $_SESSION['safenode_message'] = "Site adicionado com sucesso!";
                    $_SESSION['safenode_message_type'] = "success";
                    header('Location: sites.php');
                    exit;
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                        $message = "Este domínio já está cadastrado!";
                        $messageType = "error";
                    } else {
                        $message = "Erro ao adicionar site: " . $e->getMessage();
                        $messageType = "error";
                    }
                }
            }
        } elseif (empty($domain)) {
            $message = "O domínio é obrigatório!";
            $messageType = "error";
        }
    } elseif (isset($_POST['update_site'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        $displayName = trim($_POST['display_name'] ?? '');
        $cloudflareZoneId = trim($_POST['cloudflare_zone_id'] ?? '');
        $securityLevel = $_POST['security_level'] ?? 'medium';
        $autoBlock = isset($_POST['auto_block']) ? 1 : 0;
        $rateLimit = isset($_POST['rate_limit_enabled']) ? 1 : 0;
        $threatDetection = isset($_POST['threat_detection_enabled']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        if ($siteId > 0 && $db) {
            try {
                $stmt = $db->prepare("UPDATE safenode_sites SET display_name = ?, cloudflare_zone_id = ?, security_level = ?, auto_block = ?, rate_limit_enabled = ?, threat_detection_enabled = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$displayName ?: null, $cloudflareZoneId ?: null, $securityLevel, $autoBlock, $rateLimit, $threatDetection, $isActive, $siteId]);
                $_SESSION['safenode_message'] = "Site atualizado com sucesso!";
                $_SESSION['safenode_message_type'] = "success";
                header('Location: sites.php');
                exit;
            } catch (PDOException $e) {
                $message = "Erro ao atualizar site: " . $e->getMessage();
                $messageType = "error";
            }
        }
    } elseif (isset($_POST['delete_site'])) {
        $siteId = intval($_POST['site_id'] ?? 0);
        
        if ($siteId > 0 && $db) {
            try {
                $stmt = $db->prepare("DELETE FROM safenode_sites WHERE id = ?");
                $stmt->execute([$siteId]);
                $_SESSION['safenode_message'] = "Site removido com sucesso!";
                $_SESSION['safenode_message_type'] = "success";
                header('Location: sites.php');
                exit;
            } catch (PDOException $e) {
                $message = "Erro ao remover site: " . $e->getMessage();
                $messageType = "error";
            }
        }
    }
}

// Buscar sites
$sites = [];

if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM safenode_sites ORDER BY created_at DESC");
        $sites = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("SafeNode Sites Error: " . $e->getMessage());
    }
}

// Estatísticas por site
$siteStats = [];
foreach ($sites as $site) {
    if ($db) {
        try {
            // Estatísticas das últimas 24 horas
            $stmt = $db->prepare("SELECT 
                COUNT(*) as total_requests,
                SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as blocked,
                COUNT(DISTINCT ip_address) as unique_ips,
                SUM(CASE WHEN threat_score >= 70 THEN 1 ELSE 0 END) as high_threats
                FROM safenode_security_logs 
                WHERE (request_uri LIKE ? OR request_uri LIKE ?) 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $domainPattern = '%' . $site['domain'] . '%';
            $stmt->execute([$domainPattern, 'http://' . $site['domain'] . '%']);
            $result = $stmt->fetch();
            $siteStats[$site['id']] = $result ?: ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
        } catch (PDOException $e) {
            error_log("SafeNode Stats Error: " . $e->getMessage());
            $siteStats[$site['id']] = ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
        }
    } else {
        $siteStats[$site['id']] = ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sites - SafeNode</title>
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
                <h2 class="text-xl font-bold text-white tracking-tight">Gerenciar Sites</h2>
                <p class="text-xs text-zinc-400 mt-0.5">Configure e monitore seus domínios protegidos</p>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-xl <?php echo $messageType === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'; ?> font-semibold flex items-center gap-2">
                    <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'alert-circle'; ?>" class="w-5 h-5"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="glass-card rounded-xl p-6 mb-6">
                <div class="mb-6">
                    <h3 class="text-xl font-bold text-white mb-2">Adicionar Novo Site</h3>
                    <p class="text-sm text-zinc-400">Configure um novo domínio para proteção com SafeNode. O sistema irá monitorar e proteger automaticamente contra ameaças.</p>
                </div>
                
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="add_site" value="1">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Domínio <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="domain" required placeholder="exemplo.com" pattern="^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p class="mt-1.5 text-xs text-zinc-500">Digite o domínio sem http:// ou https:// (ex: meusite.com.br)</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Nome de Exibição
                            </label>
                            <input type="text" name="display_name" placeholder="Meu Site Principal" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p class="mt-1.5 text-xs text-zinc-500">Nome amigável para identificar o site no painel</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Cloudflare Zone ID
                            </label>
                            <input type="text" name="cloudflare_zone_id" placeholder="abc123def456..." class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <p class="mt-1.5 text-xs text-zinc-500">Opcional: ID da zona no Cloudflare para sincronização automática</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-zinc-300 mb-2">
                                Nível de Segurança
                            </label>
                            <select name="security_level" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="low">Baixo - Proteção básica</option>
                                <option value="medium" selected>Médio - Proteção recomendada</option>
                                <option value="high">Alto - Máxima proteção</option>
                                <option value="under_attack">Sob Ataque - Modo de emergência</option>
                            </select>
                            <p class="mt-1.5 text-xs text-zinc-500">Define o nível de rigor na detecção e bloqueio de ameaças</p>
                        </div>
                    </div>
                    
                    <div class="pt-4 border-t border-white/5">
                        <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 mb-4">
                            <div class="flex items-start gap-3">
                                <i data-lucide="info" class="w-5 h-5 text-blue-400 mt-0.5 flex-shrink-0"></i>
                                <div>
                                    <h4 class="text-sm font-semibold text-blue-400 mb-1">Como funciona?</h4>
                                    <ul class="text-xs text-zinc-400 space-y-1">
                                        <li>• O SafeNode monitora todas as requisições ao domínio configurado</li>
                                        <li>• Detecta automaticamente ameaças como SQL Injection, XSS, Brute Force, etc.</li>
                                        <li>• Bloqueia IPs maliciosos automaticamente conforme o nível de segurança</li>
                                        <li>• Integra com Cloudflare para sincronização de bloqueios (se configurado)</li>
                                        <li>• Gera logs detalhados de todas as atividades de segurança</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-3">
                            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-lg shadow-blue-500/20 flex items-center gap-2">
                                <i data-lucide="plus" class="w-5 h-5"></i>
                                Adicionar Site
                            </button>
                            <button type="reset" class="px-6 py-2.5 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold transition-all">
                                Limpar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (empty($sites)): ?>
                    <div class="col-span-full">
                        <div class="glass-card rounded-xl p-12 text-center">
                            <div class="w-16 h-16 bg-zinc-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                <i data-lucide="globe" class="w-8 h-8 text-zinc-500"></i>
                            </div>
                            <h3 class="text-lg font-bold text-white mb-2">Nenhum site configurado</h3>
                            <p class="text-sm text-zinc-400">Use o formulário acima para adicionar seu primeiro site</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($sites as $site): ?>
                        <?php 
                        $stats = $siteStats[$site['id']] ?? ['total_requests' => 0, 'blocked' => 0, 'unique_ips' => 0, 'high_threats' => 0];
                        $blockPercentage = $stats['total_requests'] > 0 ? round(($stats['blocked'] / $stats['total_requests']) * 100, 1) : 0;
                        ?>
                        <div class="glass-card rounded-xl p-6 hover:border-blue-500/30 transition-all">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-white">
                                            <?php echo htmlspecialchars($site['display_name'] ?: $site['domain']); ?>
                                        </h3>
                                        <?php if ($site['is_active']): ?>
                                            <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse" title="Site Ativo"></span>
                                        <?php else: ?>
                                            <span class="w-2 h-2 bg-zinc-500 rounded-full" title="Site Inativo"></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-zinc-400 font-mono"><?php echo htmlspecialchars($site['domain']); ?></p>
                                    <?php if ($site['cloudflare_zone_id']): ?>
                                        <p class="text-xs text-zinc-500 mt-1">
                                            <i data-lucide="cloud" class="w-3 h-3 inline"></i>
                                            Cloudflare conectado
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Status Badges -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg <?php 
                                    echo $site['cloudflare_status'] === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 
                                        ($site['cloudflare_status'] === 'pending' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-zinc-800 text-zinc-400 border border-white/5');
                                ?>">
                                    <i data-lucide="shield" class="w-3 h-3 mr-1"></i>
                                    Cloudflare: <?php echo ucfirst($site['cloudflare_status']); ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg <?php 
                                    echo $site['security_level'] === 'high' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 
                                        ($site['security_level'] === 'medium' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 
                                        ($site['security_level'] === 'under_attack' ? 'bg-red-600/20 text-red-300 border border-red-500/30' : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'));
                                ?>">
                                    <i data-lucide="lock" class="w-3 h-3 mr-1"></i>
                                    <?php 
                                        $levelNames = [
                                            'low' => 'Baixo',
                                            'medium' => 'Médio',
                                            'high' => 'Alto',
                                            'under_attack' => 'Sob Ataque'
                                        ];
                                        echo $levelNames[$site['security_level']] ?? ucfirst($site['security_level']);
                                    ?>
                                </span>
                            </div>

                            <!-- Estatísticas 24h -->
                            <div class="mb-4 pt-4 border-t border-white/5">
                                <div class="grid grid-cols-3 gap-3 mb-3">
                                    <div>
                                        <p class="text-xs text-zinc-500 mb-1">Requisições</p>
                                        <p class="text-lg font-bold text-white"><?php echo number_format($stats['total_requests']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 mb-1">Bloqueados</p>
                                        <p class="text-lg font-bold text-red-400"><?php echo number_format($stats['blocked']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-zinc-500 mb-1">IPs Únicos</p>
                                        <p class="text-lg font-bold text-white"><?php echo number_format($stats['unique_ips']); ?></p>
                                    </div>
                                </div>
                                <?php if ($stats['total_requests'] > 0): ?>
                                    <div class="mt-3 pt-3 border-t border-white/5">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-xs text-zinc-500">Taxa de Bloqueio</span>
                                            <span class="text-xs font-semibold <?php echo $blockPercentage > 10 ? 'text-red-400' : ($blockPercentage > 5 ? 'text-amber-400' : 'text-emerald-400'); ?>">
                                                <?php echo $blockPercentage; ?>%
                                            </span>
                                        </div>
                                        <div class="w-full h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                                            <div class="h-full <?php echo $blockPercentage > 10 ? 'bg-red-500' : ($blockPercentage > 5 ? 'bg-amber-500' : 'bg-emerald-500'); ?>" style="width: <?php echo min(100, $blockPercentage); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($stats['high_threats'] > 0): ?>
                                    <div class="mt-2 flex items-center gap-2 text-xs">
                                        <i data-lucide="alert-triangle" class="w-4 h-4 text-red-400"></i>
                                        <span class="text-red-400 font-semibold"><?php echo number_format($stats['high_threats']); ?> ameaças críticas</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Features -->
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php if ($site['auto_block']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-emerald-500/10 text-emerald-400 rounded-lg font-medium border border-emerald-500/20">
                                        <i data-lucide="shield-check" class="w-3 h-3 mr-1"></i>
                                        Auto-Block
                                    </span>
                                <?php endif; ?>
                                <?php if ($site['rate_limit_enabled']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-blue-500/10 text-blue-400 rounded-lg font-medium border border-blue-500/20">
                                        <i data-lucide="gauge" class="w-3 h-3 mr-1"></i>
                                        Rate Limit
                                    </span>
                                <?php endif; ?>
                                <?php if ($site['threat_detection_enabled']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-purple-500/10 text-purple-400 rounded-lg font-medium border border-purple-500/20">
                                        <i data-lucide="radar" class="w-3 h-3 mr-1"></i>
                                        Threat Detection
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-2 pt-4 border-t border-white/5">
                                <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente <?php echo $site['is_active'] ? 'desativar' : 'ativar'; ?> este site?');">
                                    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                    <input type="hidden" name="update_site" value="1">
                                    <input type="hidden" name="display_name" value="<?php echo htmlspecialchars($site['display_name'] ?? ''); ?>">
                                    <input type="hidden" name="cloudflare_zone_id" value="<?php echo htmlspecialchars($site['cloudflare_zone_id'] ?? ''); ?>">
                                    <input type="hidden" name="security_level" value="<?php echo htmlspecialchars($site['security_level']); ?>">
                                    <input type="hidden" name="auto_block" value="<?php echo $site['auto_block'] ? '1' : '0'; ?>">
                                    <input type="hidden" name="rate_limit_enabled" value="<?php echo $site['rate_limit_enabled'] ? '1' : '0'; ?>">
                                    <input type="hidden" name="threat_detection_enabled" value="<?php echo $site['threat_detection_enabled'] ? '1' : '0'; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $site['is_active'] ? '0' : '1'; ?>">
                                    <button type="submit" class="flex-1 px-3 py-2 <?php echo $site['is_active'] ? 'bg-amber-500/10 text-amber-400 hover:bg-amber-500/20 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20 border border-emerald-500/20'; ?> rounded-xl font-semibold text-sm transition-all">
                                        <i data-lucide="<?php echo $site['is_active'] ? 'pause' : 'play'; ?>" class="w-4 h-4 inline mr-1"></i>
                                        <?php echo $site['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                    </button>
                                </form>
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($site)); ?>)" class="flex-1 px-4 py-2 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold text-sm transition-all">
                                    <i data-lucide="edit" class="w-4 h-4 inline mr-1"></i>
                                    Editar
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('Deseja realmente remover este site? Esta ação não pode ser desfeita.');">
                                    <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                    <input type="hidden" name="delete_site" value="1">
                                    <button type="submit" class="px-4 py-2 bg-red-500/10 text-red-400 rounded-xl hover:bg-red-500/20 font-semibold text-sm transition-all border border-red-500/20">
                                        <i data-lucide="trash-2" class="w-4 h-4 inline"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div id="siteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="glass-card rounded-2xl p-6 max-w-lg w-full mx-4 border border-white/10">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-white">Editar Site</h3>
                <button onclick="closeModal()" class="text-zinc-400 hover:text-white transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form method="POST" id="siteForm">
                <input type="hidden" name="site_id" id="site_id">
                <input type="hidden" name="update_site" value="1">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Domínio</label>
                        <input type="text" id="domain" disabled class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-zinc-400">
                        <p class="mt-1.5 text-xs text-zinc-500">O domínio não pode ser alterado após criação</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Nome de Exibição</label>
                        <input type="text" name="display_name" id="display_name" placeholder="Meu Site" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Cloudflare Zone ID</label>
                        <input type="text" name="cloudflare_zone_id" id="cloudflare_zone_id" placeholder="Opcional" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-zinc-300 mb-2">Nível de Segurança</label>
                        <select name="security_level" id="security_level" class="w-full px-4 py-2.5 border border-white/10 rounded-xl bg-zinc-900/50 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="low">Baixo - Proteção básica</option>
                            <option value="medium">Médio - Proteção recomendada</option>
                            <option value="high">Alto - Máxima proteção</option>
                            <option value="under_attack">Sob Ataque - Modo de emergência</option>
                        </select>
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="auto_block" id="auto_block" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="auto_block" class="text-sm font-medium text-zinc-300">Bloqueio Automático</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="rate_limit_enabled" id="rate_limit_enabled" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="rate_limit_enabled" class="text-sm font-medium text-zinc-300">Rate Limiting</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="threat_detection_enabled" id="threat_detection_enabled" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="threat_detection_enabled" class="text-sm font-medium text-zinc-300">Detecção de Ameaças</label>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="is_active" id="is_active" class="w-4 h-4 text-blue-600 bg-zinc-800 border-white/10 rounded focus:ring-blue-500">
                            <label for="is_active" class="text-sm font-medium text-zinc-300">Site Ativo</label>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button type="button" onclick="closeModal()" class="flex-1 px-4 py-2.5 bg-zinc-800 text-zinc-300 rounded-xl hover:bg-zinc-700 font-semibold transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 font-semibold transition-all">
                        Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function openEditModal(site) {
            document.getElementById('site_id').value = site.id;
            document.getElementById('domain').value = site.domain;
            document.getElementById('display_name').value = site.display_name || '';
            document.getElementById('cloudflare_zone_id').value = site.cloudflare_zone_id || '';
            document.getElementById('security_level').value = site.security_level || 'medium';
            document.getElementById('auto_block').checked = site.auto_block == 1 || site.auto_block === true;
            document.getElementById('rate_limit_enabled').checked = site.rate_limit_enabled == 1 || site.rate_limit_enabled === true;
            document.getElementById('threat_detection_enabled').checked = site.threat_detection_enabled == 1 || site.threat_detection_enabled === true;
            document.getElementById('is_active').checked = site.is_active == 1 || site.is_active === true;
            document.getElementById('siteModal').classList.remove('hidden');
            document.getElementById('siteModal').classList.add('flex');
            lucide.createIcons();
        }

        function closeModal() {
            document.getElementById('siteModal').classList.add('hidden');
            document.getElementById('siteModal').classList.remove('flex');
        }
        
        // Validação de domínio no formulário
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
            
            const domainInput = document.querySelector('input[name="domain"]');
            if (domainInput) {
                domainInput.addEventListener('blur', function() {
                    let domain = this.value.trim();
                    // Remove http://, https://, www.
                    domain = domain.replace(/^https?:\/\//, '').replace(/^www\./, '').replace(/\/$/, '');
                    this.value = domain;
                });
            }
            
            // Auto-fechar mensagens após 5 segundos
            const messageDiv = document.querySelector('.bg-emerald-500\\/10, .bg-red-500\\/10');
            if (messageDiv) {
                setTimeout(() => {
                    messageDiv.style.transition = 'opacity 0.5s';
                    messageDiv.style.opacity = '0';
                    setTimeout(() => messageDiv.remove(), 500);
                }, 5000);
            }
        });
    </script>
</body>
</html>

