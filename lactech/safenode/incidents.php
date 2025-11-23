<?php
/**
 * SafeNode - Incidentes Agregados
 */

// Debug temporário para identificar erros em produção (remova ou desative depois)
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

// Garante que a tabela exista (caso o dump antigo esteja em uso na Hostinger)
$schemaError = null;
if ($db) {
    try {
        // Verificar se a tabela existe
        $tableCheck = $db->query("SHOW TABLES LIKE 'safenode_incidents'")->fetch();
        if (!$tableCheck) {
            // Tentar criar a tabela automaticamente
            try {
                $createTableSQL = "
                CREATE TABLE `safenode_incidents` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `ip_address` varchar(45) NOT NULL,
                  `threat_type` varchar(50) DEFAULT NULL,
                  `site_id` int(11) DEFAULT NULL,
                  `status` varchar(20) DEFAULT 'open',
                  `first_seen` timestamp NULL DEFAULT current_timestamp(),
                  `last_seen` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `total_events` int(11) DEFAULT 1,
                  `critical_events` int(11) DEFAULT 0,
                  `highest_score` int(11) DEFAULT 0,
                  `notes` text DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_inc_ip` (`ip_address`),
                  KEY `idx_inc_status` (`status`),
                  KEY `idx_inc_type` (`threat_type`),
                  KEY `idx_inc_site` (`site_id`),
                  KEY `idx_inc_last_seen` (`last_seen`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
                $db->exec($createTableSQL);
                // Tabela criada com sucesso
            } catch (Exception $createError) {
                $schemaError = "Tabela 'safenode_incidents' não existe e não foi possível criar automaticamente. Execute o script safend (1).sql no phpMyAdmin. Erro: " . $createError->getMessage();
            }
        } else {
            // Verificar colunas
            $columns = $db->query("SHOW COLUMNS FROM safenode_incidents")->fetchAll(PDO::FETCH_COLUMN);
            $required = ['id','ip_address','threat_type','site_id','status','first_seen','last_seen','total_events','critical_events','highest_score','notes'];
            $missing = array_diff($required, $columns);
            if ($missing) {
                $schemaError = "Tabela safenode_incidents incompleta. Faltando as colunas: " . implode(', ', $missing) . ". Execute o script safend (1).sql no phpMyAdmin para atualizar a estrutura.";
            }
        }
    } catch (Exception $e) {
        $schemaError = "Erro ao conectar ao banco de dados: " . $e->getMessage() . " | Verifique as credenciais em includes/config.php";
    }
} else {
    $schemaError = "Não foi possível conectar ao banco de dados. Verifique o arquivo includes/config.php";
}


$statusFilter = $_GET['status'] ?? 'open';
$statusFilter = in_array($statusFilter, ['open', 'resolved', 'all'], true) ? $statusFilter : 'open';

// Montar cláusula de status
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = 'i.status = ?';
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$incidents = [];

// Só busca incidentes se não houver erro de schema
if ($db && !isset($schemaError)) {
    try {
        $sql = "
            SELECT 
                i.*,
                s.domain as site_domain
            FROM safenode_incidents i
            LEFT JOIN safenode_sites s ON s.id = i.site_id
            $whereClause
            ORDER BY i.last_seen DESC
            LIMIT 200
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SafeNode Incidents Error: " . $e->getMessage());
        $schemaError = "Erro ao buscar incidentes: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidentes | SafeNode</title>
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
            <div class="flex items-center gap-4 md:hidden">
                <button class="text-zinc-400 hover:text-white" data-sidebar-toggle>
                    <i data-lucide="menu" class="w-6 h-6"></i>
                </button>
                <span class="font-bold text-lg text-white">SafeNode</span>
            </div>
            <div class="hidden md:block">
                <h2 class="text-xl font-bold text-white tracking-tight">Incidentes de Segurança</h2>
                <p class="text-xs text-zinc-400 mt-0.5">Agrupamento de múltiplos eventos em incidentes analisáveis</p>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-6 md:p-8 z-10">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <?php if (isset($schemaError)): ?>
                    <div class="glass-card rounded-xl p-8 border-2 border-red-500/30 bg-gradient-to-br from-red-950/20 to-red-900/10">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center flex-shrink-0">
                                <i data-lucide="alert-circle" class="w-6 h-6 text-red-400"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-2">❌ Erro de Banco de Dados</h3>
                                <p class="text-sm text-red-300 mb-4 font-semibold"><?php echo htmlspecialchars($schemaError); ?></p>
                                <div class="bg-zinc-900/50 rounded-lg p-4 border border-white/5 mb-4">
                                    <p class="text-xs text-zinc-400 mb-2">
                                        <strong class="text-white">Solução:</strong> Importe o arquivo <code class="px-2 py-1 bg-black/50 rounded text-yellow-300 font-mono">safend (1).sql</code> 
                                        mais recente no phpMyAdmin da Hostinger para criar/atualizar a tabela <code class="px-2 py-1 bg-black/50 rounded text-blue-300 font-mono">safenode_incidents</code>.
                                    </p>
                                </div>
                                <div class="flex gap-3">
                                    <a href="dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-black font-semibold text-sm hover:bg-zinc-200 transition-all">
                                        <i data-lucide="home" class="w-4 h-4"></i>
                                        Voltar ao Dashboard
                                    </a>
                                    <a href="?retry=1" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-zinc-800 text-white font-semibold text-sm hover:bg-zinc-700 transition-all border border-white/10">
                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                        Tentar Novamente
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                
                <div class="glass-card rounded-xl p-6 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white tracking-tight">Visão de Incidentes</h3>
                            <p class="text-xs text-zinc-500 mt-1">Cada incidente representa uma sequência de ataques relacionados</p>
                        </div>
                        <div class="inline-flex rounded-xl bg-zinc-900 border border-white/10 p-1 text-xs">
                            <a href="?status=open" class="px-3 py-1 rounded-lg <?php echo $statusFilter === 'open' ? 'bg-blue-600 text-white' : 'text-zinc-300 hover:bg-zinc-800'; ?> transition-all">Abertos</a>
                            <a href="?status=resolved" class="px-3 py-1 rounded-lg <?php echo $statusFilter === 'resolved' ? 'bg-blue-600 text-white' : 'text-zinc-300 hover:bg-zinc-800'; ?> transition-all">Resolvidos</a>
                            <a href="?status=all" class="px-3 py-1 rounded-lg <?php echo $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'text-zinc-300 hover:bg-zinc-800'; ?> transition-all">Todos</a>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-xl overflow-hidden">
                    <div class="p-6 border-b border-white/5 flex items-center justify-between">
                        <h3 class="font-bold text-white text-lg">Lista de Incidentes</h3>
                        <span class="text-xs text-zinc-400">Mostrando até 200 incidentes mais recentes</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4">IP</th>
                                    <th class="px-6 py-4">Tipo</th>
                                    <th class="px-6 py-4">Site</th>
                                    <th class="px-6 py-4">Eventos</th>
                                    <th class="px-6 py-4">Críticos</th>
                                    <th class="px-6 py-4">Maior Score</th>
                                    <th class="px-6 py-4">Primeiro</th>
                                    <th class="px-6 py-4">Último</th>
                                    <th class="px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php if (empty($incidents)): ?>
                                    <tr>
                                        <td colspan="10">
                                            <div class="p-12 text-center">
                                                <div class="w-16 h-16 bg-zinc-900 rounded-xl flex items-center justify-center mx-auto mb-4">
                                                    <i data-lucide="shield" class="w-8 h-8 text-zinc-500"></i>
                                                </div>
                                                <p class="text-sm text-zinc-400 font-medium">Nenhum incidente encontrado</p>
                                                <p class="text-xs text-zinc-500 mt-1">Quando o SafeNode detectar múltiplos eventos relacionados, eles aparecerão aqui.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($incidents as $incident): ?>
                                        <tr class="hover:bg-white/[0.02] transition-colors">
                                            <td class="px-6 py-4">
                                                <?php if ($incident['status'] === 'open'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/20">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 mr-1 animate-pulse"></span>
                                                        Aberto
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-800 text-zinc-400 border border-white/5">
                                                        Resolvido
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm font-semibold text-white font-mono"><?php echo htmlspecialchars($incident['ip_address']); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-800 text-zinc-300 border border-white/5">
                                                    <?php echo strtoupper(str_replace('_', ' ', htmlspecialchars($incident['threat_type'] ?? 'unknown'))); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-xs text-zinc-400 font-mono"><?php echo htmlspecialchars($incident['site_domain'] ?? '-'); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-zinc-200 font-semibold"><?php echo (int)$incident['total_events']; ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm <?php echo $incident['critical_events'] > 0 ? 'text-red-400 font-semibold' : 'text-zinc-400'; ?>">
                                                    <?php echo (int)$incident['critical_events']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-zinc-200 font-mono"><?php echo (int)$incident['highest_score']; ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-xs text-zinc-400 font-mono"><?php echo date('d/m/Y H:i:s', strtotime($incident['first_seen'])); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-xs text-zinc-400 font-mono"><?php echo date('d/m/Y H:i:s', strtotime($incident['last_seen'])); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <a href="logs.php?<?php echo http_build_query(['ip' => $incident['ip_address'], 'threat_type' => $incident['threat_type']]); ?>" class="text-blue-400 hover:text-blue-300 text-xs font-semibold">
                                                        Ver logs
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <?php endif; ?>
                
            </div>
        </div>
    </main>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>


