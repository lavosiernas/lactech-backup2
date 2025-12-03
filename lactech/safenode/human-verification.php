<?php
/**
 * SafeNode - Gerenciamento de Verificação Humana
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
require_once __DIR__ . '/includes/HVAPIKeyManager.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;

if (!$userId) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        $name = $_POST['name'] ?? 'Verificação Humana';
        $allowedDomains = trim($_POST['allowed_domains'] ?? '');
        $rateLimit = !empty($_POST['rate_limit']) ? (int)$_POST['rate_limit'] : 60;
        $maxTokenAge = !empty($_POST['max_token_age']) ? (int)$_POST['max_token_age'] : 3600;
        
        // Validar rate limit (mínimo 10, máximo 1000)
        $rateLimit = max(10, min(1000, $rateLimit));
        
        // Validar max token age (mínimo 300s = 5min, máximo 86400s = 24h)
        $maxTokenAge = max(300, min(86400, $maxTokenAge));
        
        $result = HVAPIKeyManager::generateKey($userId, $name, $allowedDomains ?: null, $rateLimit, $maxTokenAge);
        if ($result) {
            $message = 'API key gerada com sucesso!';
            $messageType = 'success';
        } else {
            $message = 'Erro ao gerar API key.';
            $messageType = 'error';
        }
    } elseif ($action === 'deactivate') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if (HVAPIKeyManager::deactivateKey($keyId, $userId)) {
            $message = 'API key desativada com sucesso.';
            $messageType = 'success';
        } else {
            $message = 'Erro ao desativar API key.';
            $messageType = 'error';
        }
    } elseif ($action === 'activate') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if (HVAPIKeyManager::activateKey($keyId, $userId)) {
            $message = 'API key ativada com sucesso.';
            $messageType = 'success';
        } else {
            $message = 'Erro ao ativar API key.';
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if (HVAPIKeyManager::deleteKey($keyId, $userId)) {
            $message = 'API key deletada com sucesso.';
            $messageType = 'success';
        } else {
            $message = 'Erro ao deletar API key.';
            $messageType = 'error';
        }
    }
}

// Obter API keys do usuário
$apiKeys = HVAPIKeyManager::getUserKeys($userId);

// Obter URL base (detecta automaticamente se é produção ou desenvolvimento)
$baseUrl = getSafeNodeBaseUrl();

?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação Humana | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: #030303;
            color: #a1a1aa;
            font-family: 'Inter', sans-serif;
            font-size: 0.92em;
            -webkit-font-smoothing: antialiased;
        }
        
        .glass {
            background: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .code-block {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 16px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            overflow-x: auto;
        }
    </style>
</head>
<body class="h-full">
    <div class="min-h-screen bg-dark-950 p-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="dashboard.php" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white mb-4">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    <span>Voltar ao Dashboard</span>
                </a>
                <h1 class="text-3xl font-bold text-white mb-2">Verificação Humana</h1>
                <p class="text-zinc-500">Gerencie suas API keys e integre verificação humana em seus sites</p>
            </div>

            <!-- Mensagem -->
            <?php if ($message): ?>
            <div class="mb-6 glass rounded-xl p-4 <?php echo $messageType === 'success' ? 'border-green-500/30 bg-green-500/10' : 'border-red-500/30 bg-red-500/10'; ?>">
                <p class="text-white"><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>

            <!-- Gerar Nova API Key -->
            <div class="glass rounded-2xl p-6 mb-8">
                <h2 class="text-xl font-semibold text-white mb-4">Gerar Nova API Key</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="generate">
                    
                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-2">Nome da API Key</label>
                        <input type="text" name="name" placeholder="Verificação Humana" value="Verificação Humana" 
                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white placeholder-zinc-500 focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/10">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-zinc-400 mb-2">Domínios Permitidos (opcional)</label>
                        <input type="text" name="allowed_domains" placeholder="exemplo.com, www.exemplo.com" 
                            class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white placeholder-zinc-500 focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/10">
                        <p class="text-xs text-zinc-500 mt-1">Separe múltiplos domínios por vírgula. Deixe vazio para permitir qualquer domínio.</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-2">Rate Limit (req/min)</label>
                            <input type="number" name="rate_limit" value="60" min="10" max="1000" 
                                class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/10">
                            <p class="text-xs text-zinc-500 mt-1">Máximo de requisições por minuto (10-1000)</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-zinc-400 mb-2">Idade Máxima do Token (segundos)</label>
                            <input type="number" name="max_token_age" value="3600" min="300" max="86400" 
                                class="w-full px-4 py-2 bg-white/5 border border-white/10 rounded-xl text-white focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/10">
                            <p class="text-xs text-zinc-500 mt-1">Tempo de expiração do token (300-86400s)</p>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full md:w-auto px-6 py-2.5 bg-white text-black rounded-xl font-semibold hover:bg-white/90 transition-colors">
                        Gerar API Key
                    </button>
                </form>
            </div>

            <!-- Lista de API Keys -->
            <div class="glass rounded-2xl p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-semibold text-white">Suas API Keys</h2>
                    <a href="api-monitor.php" class="px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-colors text-sm flex items-center gap-2">
                        <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                        Monitoramento
                    </a>
                </div>
                
                <?php if (empty($apiKeys)): ?>
                    <p class="text-zinc-500 text-center py-8">Nenhuma API key criada ainda. Gere uma acima para começar.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($apiKeys as $key): ?>
                        <div class="bg-dark-900/50 rounded-xl p-6 border border-white/5">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-white font-semibold"><?php echo htmlspecialchars($key['name']); ?></h3>
                                        <?php if ($key['is_active']): ?>
                                            <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-lg">Ativa</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 bg-zinc-500/20 text-zinc-400 text-xs rounded-lg">Inativa</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-sm text-zinc-500 mb-1">
                                        Criada em: <?php 
                                            $date = new DateTime($key['created_at'], new DateTimeZone('UTC'));
                                            $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                                            echo $date->format('d/m/Y H:i');
                                        ?>
                                    </p>
                                    <?php if ($key['last_used_at']): ?>
                                        <p class="text-sm text-zinc-500 mb-1">
                                            Último uso: <?php 
                                                $date = new DateTime($key['last_used_at'], new DateTimeZone('UTC'));
                                                $date->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                                                echo $date->format('d/m/Y H:i');
                                            ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-sm text-zinc-500 mb-1">
                                        Usos: <?php echo number_format($key['usage_count']); ?>
                                    </p>
                                    <?php if (!empty($key['allowed_domains'])): ?>
                                        <p class="text-sm text-zinc-400 mb-1">
                                            <i data-lucide="globe" class="w-3 h-3 inline"></i> 
                                            Domínios: <?php echo htmlspecialchars($key['allowed_domains']); ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="text-sm text-zinc-400">
                                        <i data-lucide="gauge" class="w-3 h-3 inline"></i> 
                                        Rate Limit: <?php echo (int)($key['rate_limit_per_minute'] ?? 60); ?> req/min • 
                                        Token expira em: <?php echo (int)($key['max_token_age'] ?? 3600); ?>s
                                    </p>
                                </div>
                                <div class="flex gap-2">
                                    <a href="api-monitor.php?key_id=<?php echo $key['id']; ?>" class="px-4 py-2 bg-blue-600/20 text-blue-400 rounded-lg hover:bg-blue-600/30 transition-colors text-sm flex items-center gap-2">
                                        <i data-lucide="bar-chart-2" class="w-3 h-3"></i>
                                        Monitorar
                                    </a>
                                    <?php if ($key['is_active']): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="deactivate">
                                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-zinc-800 text-zinc-300 rounded-lg hover:bg-zinc-700 transition-colors text-sm">
                                                Desativar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                                Ativar
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Tem certeza que deseja deletar esta API key?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                                        <button type="submit" class="px-4 py-2 bg-red-600/20 text-red-400 rounded-lg hover:bg-red-600/30 transition-colors text-sm">
                                            Deletar
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Código de Integração -->
                            <?php if ($key['is_active']): ?>
                            <div class="mt-4 pt-4 border-t border-white/5">
                                <h4 class="text-sm font-semibold text-white mb-3">Código de Integração</h4>
                                <div class="code-block mb-3">
                                    <?php 
                                    $embedCode = HVAPIKeyManager::generateEmbedCode($key['api_key'], $baseUrl);
                                    echo htmlspecialchars($embedCode);
                                    ?>
                                </div>
                                <button 
                                    onclick="copyCode(this)" 
                                    data-code="<?php echo htmlspecialchars($embedCode, ENT_QUOTES); ?>"
                                    class="px-4 py-2 bg-white/10 text-white rounded-lg hover:bg-white/20 transition-colors text-sm"
                                >
                                    Copiar Código
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        function copyCode(button) {
            const code = button.getAttribute('data-code');
            navigator.clipboard.writeText(code).then(() => {
                const originalText = button.textContent;
                button.textContent = 'Copiado!';
                button.classList.add('bg-green-600/20', 'text-green-400');
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('bg-green-600/20', 'text-green-400');
                }, 2000);
            });
        }
    </script>
    
    <!-- Security Scripts - Previne download de código -->
    <script src="includes/security-scripts.js"></script>
</body>
</html>

