<?php
if (!isset($blockMessage)) {
    $blockMessage = "Acesso negado por segurança";
}

$reasonLabels = [
    'ip_blocked' => 'IP bloqueado pelo administrador',
    'rate_limit' => 'Excesso de requisições detectado',
    'honeypot' => 'Tentativa de acesso a rota restrita',
    'sql_injection' => 'Tentativa de injeção de código detectada',
    'xss' => 'Tentativa de XSS detectada',
    'brute_force' => 'Tentativas excessivas de login',
    'ddos' => 'Alto volume de tráfego suspeito',
    'under_attack' => 'Site sob proteção reforçada',
    'geo_block' => 'A região de origem não é permitida',
    'geo_allow_only' => 'Acesso restrito a países autorizados',
];

$friendlyReason = $reasonLabels[$blockReason ?? ''] ?? 'Requisição não autorizada';
$rayId = $rayId ?? strtoupper(substr(bin2hex(random_bytes(8)), 0, 16));
$ipAddress = htmlspecialchars($ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
$siteDomain = htmlspecialchars($siteDomain ?? ($_SERVER['HTTP_HOST'] ?? 'SafeNode'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Restrito | <?php echo $siteDomain; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background-color: #000000;
            color: #e4e4e7;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 520px;
            background: rgba(24, 24, 27, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(16px);
            text-align: center;
        }
        .pulse-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #f87171;
            margin: 0 auto 16px auto;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.9); opacity: 0.7; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.7; }
        }
        h1 {
            margin: 0 0 12px;
            font-size: 1.5rem;
            color: #fff;
        }
        p {
            margin: 4px 0;
            color: #a1a1aa;
            font-size: 0.95rem;
        }
        .details {
            margin-top: 24px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(9, 9, 11, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-align: left;
            font-family: 'JetBrains Mono', 'Inter', monospace;
            font-size: 0.85rem;
        }
        .details div {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            color: #a1a1aa;
        }
        .details span {
            color: #f4f4f5;
        }
        .cta {
            margin-top: 24px;
            font-size: 0.9rem;
            color: #71717a;
        }
        .cta strong {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="pulse-dot"></div>
        <h1><?php echo htmlspecialchars($blockMessage); ?></h1>
        <p><?php echo htmlspecialchars($friendlyReason); ?></p>
        <p>Se você acredita que isso é um engano, entre em contato com o responsável pelo site.</p>

        <div class="details">
            <div>
                <span>Site:</span>
                <span><?php echo $siteDomain; ?></span>
            </div>
            <div>
                <span>IP:</span>
                <span><?php echo $ipAddress; ?></span>
            </div>
            <div>
                <span>Referência:</span>
                <span><?php echo $rayId; ?></span>
            </div>
            <div>
                <span>Motivo:</span>
                <span><?php echo htmlspecialchars($blockReason ?? 'blocked'); ?></span>
            </div>
        </div>

        <p class="cta">
            <strong>SafeNode</strong> protege este site contra ataques e acessos não autorizados.
        </p>
    </div>
</body>
</html>
<?php
/**
 * SafeNode - Blocked Page Template
 * Página de bloqueio personalizada (Estilo Cloudflare/Enterprise)
 */

// Recebe variáveis do contexto (Middleware)
// $ipAddress, $blockReason, $rayId, $siteDomain

// Previne acesso direto
if (!defined('SAFENODE_VERSION') && !isset($ipAddress)) {
    http_response_code(403);
    exit('Access Denied');
}

$rayId = $rayId ?? substr(md5(uniqid()), 0, 16);
$ipAddress = $ipAddress ?? $_SERVER['REMOTE_ADDR'];
$blockReason = $blockReason ?? 'Access denied due to security policy.';
$siteDomain = $siteDomain ?? $_SERVER['HTTP_HOST'];
$currentYear = date('Y');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | <?php echo htmlspecialchars($siteDomain); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #09090b; color: #e4e4e7; }
        .glass-panel {
            background: rgba(24, 24, 27, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
    </style>
</head>
<body class="h-screen flex flex-col items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Background Elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden -z-10">
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-red-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-orange-500/5 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-2xl w-full glass-panel rounded-2xl shadow-2xl overflow-hidden animate-fade-in">
        <!-- Header -->
        <div class="bg-red-500/10 border-b border-red-500/20 p-6 flex items-center gap-4">
            <div class="p-3 bg-red-500/20 rounded-full text-red-500">
                <i data-lucide="shield-alert" class="w-8 h-8"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Access Denied</h1>
                <p class="text-red-400 text-sm font-medium">Security Violation Detected</p>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8 space-y-6">
            <div class="text-zinc-300 leading-relaxed">
                <p class="mb-4">
                    The SafeNode Firewall has blocked your request to <strong class="text-white"><?php echo htmlspecialchars($siteDomain); ?></strong>.
                </p>
                <p class="text-sm text-zinc-400">
                    This action was triggered by a security rule. If you believe this is a mistake, please contact the site administrator and provide the Ray ID below.
                </p>
            </div>

            <!-- Details Box -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-black/30 p-4 rounded-xl border border-white/5">
                    <div class="text-xs text-zinc-500 uppercase tracking-wider mb-1">Your IP Address</div>
                    <div class="text-lg font-mono text-white font-semibold"><?php echo htmlspecialchars($ipAddress); ?></div>
                </div>
                <div class="bg-black/30 p-4 rounded-xl border border-white/5">
                    <div class="text-xs text-zinc-500 uppercase tracking-wider mb-1">Ray ID</div>
                    <div class="text-lg font-mono text-blue-400 font-semibold"><?php echo $rayId; ?></div>
                </div>
            </div>

            <div class="bg-zinc-900/50 p-4 rounded-xl border border-white/5">
                <div class="text-xs text-zinc-500 uppercase tracking-wider mb-1">Reason</div>
                <div class="text-sm font-mono text-red-300"><?php echo htmlspecialchars($blockReason); ?></div>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-4 bg-black/20 border-t border-white/5 flex justify-between items-center text-xs text-zinc-500">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Protected by <strong>SafeNode</strong></span>
            </div>
            <div>&copy; <?php echo $currentYear; ?></div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>

