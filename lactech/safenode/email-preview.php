<?php
/**
 * SafeNode - Preview dos Templates de E-mail
 * Visualize como os emails ficarão antes de enviar
 */

require_once __DIR__ . '/includes/EmailService.php';

$emailService = SafeNodeEmailService::getInstance();

// Usar reflexão para acessar os métodos privados (apenas para preview)
$reflection = new ReflectionClass($emailService);

// Template de Manutenção
$maintenanceMethod = $reflection->getMethod('getMaintenanceTemplate');
$maintenanceMethod->setAccessible(true);
$maintenanceHtml = $maintenanceMethod->invoke($emailService, 'João Silva');

// Template de Sistema Online
$onlineMethod = $reflection->getMethod('getSystemOnlineTemplate');
$onlineMethod->setAccessible(true);
$onlineHtml = $onlineMethod->invoke($emailService, 'João Silva');

// Tipo de preview (padrão: maintenance)
$type = $_GET['type'] ?? 'maintenance';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview - E-mails SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .iframe-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        iframe {
            background: white;
        }
    </style>
</head>
<body class="bg-zinc-950 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">📧 Preview de E-mails</h1>
                <p class="text-zinc-400">Visualize como os e-mails ficarão para os usuários</p>
            </div>
            <a href="admin-emails.php" class="px-4 py-2 bg-white text-black rounded-lg font-semibold hover:bg-zinc-200 transition-colors">
                Voltar ao Admin
            </a>
        </div>

        <!-- Tabs -->
        <div class="flex gap-4 mb-6">
            <button onclick="showPreview('maintenance')" id="tab-maintenance" 
                    class="px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 <?php echo $type === 'maintenance' ? 'bg-orange-500 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700'; ?>">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                Notificação de Manutenção
            </button>
            <button onclick="showPreview('online')" id="tab-online"
                    class="px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 <?php echo $type === 'online' ? 'bg-green-500 text-white' : 'bg-zinc-800 text-zinc-400 hover:bg-zinc-700'; ?>">
                <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                Sistema Reativado
            </button>
        </div>

        <!-- Grid com Desktop e Mobile Preview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Desktop Preview -->
            <div>
                <div class="mb-4 flex items-center gap-2">
                    <i data-lucide="monitor" class="w-5 h-5 text-zinc-400"></i>
                    <h2 class="text-lg font-semibold text-white">Visualização Desktop</h2>
                </div>
                <div class="iframe-container">
                    <iframe id="desktop-preview" class="w-full h-[600px] border-0"></iframe>
                </div>
            </div>

            <!-- Mobile Preview -->
            <div>
                <div class="mb-4 flex items-center gap-2">
                    <i data-lucide="smartphone" class="w-5 h-5 text-zinc-400"></i>
                    <h2 class="text-lg font-semibold text-white">Visualização Mobile</h2>
                </div>
                <div class="iframe-container mx-auto" style="max-width: 375px;">
                    <iframe id="mobile-preview" class="w-full h-[600px] border-0"></iframe>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="mt-8 bg-zinc-900 border border-zinc-800 rounded-xl p-6">
            <div class="flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-400 mt-0.5"></i>
                <div>
                    <h3 class="text-white font-semibold mb-2">Informações sobre os E-mails</h3>
                    <ul class="text-zinc-400 text-sm space-y-2">
                        <li><strong class="text-white">Nome do usuário:</strong> João Silva (exemplo)</li>
                        <li><strong class="text-white">Remetente:</strong> SafeNode Security &lt;noreply@safenode.com&gt;</li>
                        <li><strong class="text-white">Assunto (Manutenção):</strong> 🔧 Sistema em Manutenção - SafeNode</li>
                        <li><strong class="text-white">Assunto (Online):</strong> ✅ Sistema Online - SafeNode</li>
                        <li><strong class="text-white">Formato:</strong> HTML responsivo (funciona em todos os clientes de email)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Detalhes Técnicos -->
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i data-lucide="palette" class="w-4 h-4"></i>
                    Design
                </h4>
                <ul class="text-zinc-400 text-sm space-y-1">
                    <li>✓ Fundo preto (#000000)</li>
                    <li>✓ Gradientes modernos (laranja/verde)</li>
                    <li>✓ Tipografia legível</li>
                    <li>✓ Ícones emoji para compatibilidade</li>
                </ul>
            </div>
            <div class="bg-zinc-900 border border-zinc-800 rounded-xl p-5">
                <h4 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i data-lucide="smartphone" class="w-4 h-4"></i>
                    Compatibilidade
                </h4>
                <ul class="text-zinc-400 text-sm space-y-1">
                    <li>✓ Gmail, Outlook, Apple Mail</li>
                    <li>✓ Webmail e aplicativos mobile</li>
                    <li>✓ Layout responsivo automático</li>
                    <li>✓ Fallback para clientes antigos</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Templates armazenados
        const templates = {
            maintenance: <?php echo json_encode($maintenanceHtml); ?>,
            online: <?php echo json_encode($onlineHtml); ?>
        };

        // Carregar preview inicial
        showPreview('<?php echo $type; ?>');

        function showPreview(type) {
            const desktopIframe = document.getElementById('desktop-preview');
            const mobileIframe = document.getElementById('mobile-preview');
            
            // Atualizar conteúdo dos iframes
            desktopIframe.srcdoc = templates[type];
            mobileIframe.srcdoc = templates[type];

            // Atualizar tabs
            document.getElementById('tab-maintenance').className = type === 'maintenance' 
                ? 'px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 bg-orange-500 text-white'
                : 'px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 bg-zinc-800 text-zinc-400 hover:bg-zinc-700';
            
            document.getElementById('tab-online').className = type === 'online'
                ? 'px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 bg-green-500 text-white'
                : 'px-6 py-3 rounded-lg font-semibold transition-all flex items-center gap-2 bg-zinc-800 text-zinc-400 hover:bg-zinc-700';

            // Recriar ícones Lucide
            lucide.createIcons();

            // Atualizar URL (opcional)
            const url = new URL(window.location);
            url.searchParams.set('type', type);
            window.history.pushState({}, '', url);
        }
    </script>
</body>
</html>


