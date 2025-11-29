<?php
session_start();
require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$message = '';
$messageType = '';

// Processar formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        if (!$db) {
            $message = 'Erro: Não foi possível conectar ao banco de dados';
            $messageType = 'error';
        } elseif (!$userId) {
            $message = 'Erro: Usuário não identificado. Faça login novamente';
            $messageType = 'error';
        } else {
            $domain = trim($_POST['domain'] ?? '');
            $displayName = trim($_POST['display_name'] ?? '');
            $securityLevel = $_POST['security_level'] ?? 'medium';
            
            if (empty($domain)) {
                $message = 'Domínio é obrigatório';
                $messageType = 'error';
            } else {
            // Validar formato do domínio
            $domain = strtolower($domain);
            $domain = preg_replace('/^https?:\/\//', '', $domain); // Remover http://
            $domain = preg_replace('/\/$/', '', $domain); // Remover / no final
            
            if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $domain)) {
                $message = 'Formato de domínio inválido';
                $messageType = 'error';
            } else {
                try {
                    // Verificar se já existe
                    $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE domain = ? AND user_id = ?");
                    $stmt->execute([$domain, $userId]);
                    if ($stmt->fetch()) {
                        $message = 'Este domínio já está cadastrado';
                        $messageType = 'error';
                    } else {
                        $stmt = $db->prepare("
                            INSERT INTO safenode_sites 
                            (user_id, domain, display_name, security_level, is_active, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, 1, NOW(), NOW())
                        ");
                        $stmt->execute([$userId, $domain, $displayName ?: $domain, $securityLevel]);
                        $message = 'Site cadastrado com sucesso!';
                        $messageType = 'success';
                    }
                } catch (PDOException $e) {
                    error_log("SafeNode Create Site Error: " . $e->getMessage());
                    $errorMsg = $e->getMessage();
                    
                    // Mensagens mais específicas
                    if (strpos($errorMsg, 'Table') !== false && strpos($errorMsg, "doesn't exist") !== false) {
                        $message = 'Erro: Tabela safenode_sites não existe. Execute o script banco.sql';
                    } elseif (strpos($errorMsg, 'user_id') !== false) {
                        $message = 'Erro: Problema com user_id. Verifique se está logado corretamente.';
                    } elseif (strpos($errorMsg, 'Duplicate entry') !== false) {
                        $message = 'Este domínio já está cadastrado para outro usuário';
                    } else {
                        $message = 'Erro ao cadastrar site: ' . htmlspecialchars(substr($errorMsg, 0, 100));
                    }
                    $messageType = 'error';
                } catch (Exception $e) {
                    error_log("SafeNode Create Site General Error: " . $e->getMessage());
                    $message = 'Erro geral: ' . htmlspecialchars($e->getMessage());
                    $messageType = 'error';
                }
            }
            }
        }
    } elseif ($action === 'delete' && $db) {
        $siteId = (int)($_POST['site_id'] ?? 0);
        if ($siteId > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM safenode_sites WHERE id = ? AND user_id = ?");
                $stmt->execute([$siteId, $userId]);
                if ($stmt->rowCount() > 0) {
                    $message = 'Site removido com sucesso';
                    $messageType = 'success';
                } else {
                    $message = 'Site não encontrado';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                error_log("SafeNode Delete Site Error: " . $e->getMessage());
                $message = 'Erro ao remover site';
                $messageType = 'error';
            }
        }
    } elseif ($action === 'toggle' && $db) {
        $siteId = (int)($_POST['site_id'] ?? 0);
        if ($siteId > 0) {
            try {
                $stmt = $db->prepare("UPDATE safenode_sites SET is_active = NOT is_active WHERE id = ? AND user_id = ?");
                $stmt->execute([$siteId, $userId]);
                $message = 'Status do site atualizado';
                $messageType = 'success';
            } catch (PDOException $e) {
                error_log("SafeNode Toggle Site Error: " . $e->getMessage());
                $message = 'Erro ao atualizar site';
                $messageType = 'error';
            }
        }
    }
}

// Buscar sites do usuário
$sites = [];
if ($db && $userId) {
    try {
        $stmt = $db->prepare("
            SELECT id, domain, display_name, security_level, is_active, created_at, updated_at,
                   (SELECT COUNT(*) FROM safenode_security_logs WHERE site_id = safenode_sites.id) as total_logs
            FROM safenode_sites 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("SafeNode List Sites Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Sites - SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        * { font-family: 'Inter', sans-serif; }
        .glass-card { background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="bg-black text-white min-h-screen">
    <div class="flex h-screen overflow-hidden">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        
        <main class="flex-1 overflow-y-auto">
            <div class="max-w-7xl mx-auto p-6">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-white mb-2 flex items-center gap-3">
                        <i data-lucide="globe" class="w-8 h-8 text-blue-400"></i>
                        Gerenciar Sites
                    </h1>
                    <p class="text-zinc-400">Cadastre e gerencie os sites protegidos pelo SafeNode</p>
                </div>

                <?php if ($message): ?>
                <div class="glass-card rounded-xl p-4 mb-6 <?php echo $messageType === 'success' ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-red-500/10 border-red-500/20'; ?>">
                    <p class="<?php echo $messageType === 'success' ? 'text-emerald-400' : 'text-red-400'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Formulário de Cadastro -->
                <div class="glass-card rounded-xl p-6 mb-6">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="plus-circle" class="w-6 h-6 text-blue-400"></i>
                        Cadastrar Novo Site
                    </h2>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-2">Domínio *</label>
                                <input type="text" name="domain" required 
                                    placeholder="exemplo.com" 
                                    class="w-full px-4 py-2 bg-zinc-900 border border-white/10 rounded-lg text-white focus:border-blue-500 focus:outline-none">
                                <p class="text-xs text-zinc-500 mt-1">Apenas o domínio, sem http:// ou https://</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-zinc-400 mb-2">Nome de Exibição</label>
                                <input type="text" name="display_name" 
                                    placeholder="Meu Site Principal" 
                                    class="w-full px-4 py-2 bg-zinc-900 border border-white/10 rounded-lg text-white focus:border-blue-500 focus:outline-none">
                                <p class="text-xs text-zinc-500 mt-1">Nome amigável (opcional)</p>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-2">Nível de Segurança</label>
                            <select name="security_level" class="w-full px-4 py-2 bg-zinc-900 border border-white/10 rounded-lg text-white focus:border-blue-500 focus:outline-none">
                                <option value="low">Baixo - Menos bloqueios</option>
                                <option value="medium" selected>Médio - Balanceado</option>
                                <option value="high">Alto - Mais bloqueios</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full md:w-auto px-6 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition-colors">
                            <i data-lucide="save" class="w-4 h-4 inline mr-2"></i>
                            Cadastrar Site
                        </button>
                    </form>
                </div>

                <!-- Lista de Sites -->
                <div class="glass-card rounded-xl p-6">
                    <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                        <i data-lucide="list" class="w-6 h-6 text-zinc-400"></i>
                        Meus Sites (<?php echo count($sites); ?>)
                    </h2>
                    
                    <?php if (empty($sites)): ?>
                        <div class="text-center py-12 text-zinc-500">
                            <i data-lucide="globe" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                            <p>Nenhum site cadastrado ainda</p>
                            <p class="text-sm mt-2">Cadastre seu primeiro site acima</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($sites as $site): ?>
                                <div class="p-4 rounded-lg bg-zinc-900/50 border border-white/5 hover:border-white/10 transition-all">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <h3 class="text-lg font-bold text-white">
                                                    <?php echo htmlspecialchars($site['display_name'] ?: $site['domain']); ?>
                                                </h3>
                                                <?php if ($site['is_active']): ?>
                                                    <span class="px-2 py-1 rounded text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Ativo</span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 rounded text-xs font-bold bg-zinc-500/10 text-zinc-400 border border-zinc-500/20">Inativo</span>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $levelColors = [
                                                    'low' => 'blue',
                                                    'medium' => 'amber',
                                                    'high' => 'red',
                                                    'under_attack' => 'red'
                                                ];
                                                $levelLabels = [
                                                    'low' => 'Baixo',
                                                    'medium' => 'Médio',
                                                    'high' => 'Alto',
                                                    'under_attack' => 'Sob Ataque'
                                                ];
                                                $levelColor = $levelColors[$site['security_level']] ?? 'amber';
                                                $levelLabel = $levelLabels[$site['security_level']] ?? 'Médio';
                                                
                                                if ($levelColor === 'blue') {
                                                    $badgeClass = 'bg-blue-500/10 text-blue-400 border-blue-500/20';
                                                } elseif ($levelColor === 'amber') {
                                                    $badgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                                                } else {
                                                    $badgeClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                                                }
                                                ?>
                                                <span class="px-2 py-1 rounded text-xs font-bold <?php echo $badgeClass; ?>">
                                                    Segurança: <?php echo $levelLabel; ?>
                                                </span>
                                            </div>
                                            
                                            <p class="text-sm text-zinc-400 font-mono mb-1"><?php echo htmlspecialchars($site['domain']); ?></p>
                                            <p class="text-xs text-zinc-500">
                                                <?php echo (int)$site['total_logs']; ?> eventos registrados • 
                                                Cadastrado em <?php echo date('d/m/Y', strtotime($site['created_at'])); ?>
                                            </p>
                                        </div>
                                        
                                        <div class="flex items-center gap-2 ml-4">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                                <button type="submit" class="px-3 py-1 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-sm transition-colors">
                                                    <?php echo $site['is_active'] ? 'Desativar' : 'Ativar'; ?>
                                                </button>
                                            </form>
                                            
                                            <a href="dashboard.php?view_site=<?php echo $site['id']; ?>" 
                                               class="px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-sm transition-colors">
                                                Ver Dashboard
                                            </a>
                                            
                                            <form method="POST" class="inline" 
                                                  onsubmit="return confirm('Tem certeza que deseja remover este site? Esta ação não pode ser desfeita.');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="site_id" value="<?php echo $site['id']; ?>">
                                                <button type="submit" class="px-3 py-1 rounded-lg bg-red-600 hover:bg-red-700 text-sm transition-colors">
                                                    Remover
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Processar redirect
        <?php if (isset($_GET['redirect']) && $_GET['redirect'] === 'dashboard'): ?>
            window.location.href = 'dashboard.php';
        <?php endif; ?>
        
        // Inicializar ícones
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>

