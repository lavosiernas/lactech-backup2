<?php
/**
 * SafeNode - Página de Desafio Visual
 * Mostra desafio para verificação humana antes de permitir acesso
 */

session_start();

// Verificar se há token de desafio na sessão
$challengeToken = $_SESSION['safenode_challenge_token'] ?? null;
$originalUrl = $_SESSION['safenode_challenge_original_url'] ?? '/';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Se não tem token, gerar um novo
if (!$challengeToken) {
    $challengeToken = bin2hex(random_bytes(16));
    $_SESSION['safenode_challenge_token'] = $challengeToken;
    $_SESSION['safenode_challenge_time'] = time();
    $_SESSION['safenode_challenge_original_url'] = $_SERVER['REQUEST_URI'] ?? '/';
}

// Processar validação do desafio
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['challenge_response'])) {
    $response = $_POST['challenge_response'] ?? '';
    $expected = $_SESSION['safenode_challenge_answer'] ?? '';
    
    if ($response === $expected && !empty($expected)) {
        // Desafio passou - marcar como verificado
        $_SESSION['safenode_challenge_verified'] = true;
        $_SESSION['safenode_challenge_verified_time'] = time();
        $_SESSION['safenode_challenge_verified_ip'] = $ipAddress;
        
        // Registrar sucesso no banco (se possível)
        if (file_exists(__DIR__ . '/includes/config.php')) {
            require_once __DIR__ . '/includes/config.php';
            $db = getSafeNodeDatabase();
            if ($db) {
                try {
                    // Identificar site
                    $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
                    $domain = preg_replace('/^www\./', '', $domain);
                    $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE domain = ? AND is_active = 1 LIMIT 1");
                    $stmt->execute([$domain]);
                    $site = $stmt->fetch();
                    
                    if ($site) {
                        $stmt = $db->prepare("
                            INSERT INTO safenode_human_verification_logs 
                            (site_id, ip_address, event_type, request_uri, request_method, user_agent, referer, created_at) 
                            VALUES (?, ?, 'human_validated', ?, 'GET', ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $site['id'],
                            $ipAddress,
                            $originalUrl,
                            $_SERVER['HTTP_USER_AGENT'] ?? null,
                            $_SERVER['HTTP_REFERER'] ?? null
                        ]);
                    }
                } catch (PDOException $e) {
                    error_log("SafeNode Challenge Success Log Error: " . $e->getMessage());
                }
            }
        }
        
        // Redirecionar para URL original
        header('Location: ' . $originalUrl);
        exit;
    } else {
        $error = 'Resposta incorreta. Tente novamente.';
        // Gerar novo desafio
        unset($_SESSION['safenode_challenge_answer']);
        
        // Registrar falha no banco (se possível)
        if (file_exists(__DIR__ . '/includes/config.php')) {
            require_once __DIR__ . '/includes/config.php';
            $db = getSafeNodeDatabase();
            if ($db) {
                try {
                    $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
                    $domain = preg_replace('/^www\./', '', $domain);
                    $stmt = $db->prepare("SELECT id FROM safenode_sites WHERE domain = ? AND is_active = 1 LIMIT 1");
                    $stmt->execute([$domain]);
                    $site = $stmt->fetch();
                    
                    if ($site) {
                        $stmt = $db->prepare("
                            INSERT INTO safenode_human_verification_logs 
                            (site_id, ip_address, event_type, request_uri, request_method, user_agent, referer, created_at) 
                            VALUES (?, ?, 'bot_blocked', ?, 'POST', ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $site['id'],
                            $ipAddress,
                            $_SERVER['REQUEST_URI'] ?? '/challenge-page.php',
                            $_SERVER['HTTP_USER_AGENT'] ?? null,
                            $_SERVER['HTTP_REFERER'] ?? null
                        ]);
                    }
                } catch (PDOException $e) {
                    error_log("SafeNode Challenge Fail Log Error: " . $e->getMessage());
                }
            }
        }
    }
}

// Gerar novo desafio se não existir
if (!isset($_SESSION['safenode_challenge_answer'])) {
    // Desafio simples: números aleatórios
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $answer = $num1 + $num2;
    $_SESSION['safenode_challenge_answer'] = (string)$answer;
    $_SESSION['safenode_challenge_question'] = "$num1 + $num2";
}

$question = $_SESSION['safenode_challenge_question'] ?? '2 + 2';
$siteDomain = $_SERVER['HTTP_HOST'] ?? 'SafeNode';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Segurança | <?php echo htmlspecialchars($siteDomain); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #000000; color: #e4e4e7; }
        .glass {
            background: rgba(24, 24, 27, 0.8);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full glass rounded-2xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-500/20 rounded-full mb-4">
                <i data-lucide="shield-check" class="w-8 h-8 text-blue-400"></i>
            </div>
            <h1 class="text-2xl font-bold text-white mb-2">Verificação de Segurança</h1>
            <p class="text-zinc-400 text-sm">Complete o desafio abaixo para continuar</p>
        </div>

        <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-500/20 border border-red-500/30 rounded-lg text-red-400 text-sm">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div class="bg-black/30 p-6 rounded-xl border border-white/5">
                <label class="block text-sm text-zinc-400 mb-3">Resolva esta conta:</label>
                <div class="text-center mb-4">
                    <div class="inline-block bg-white/5 px-6 py-4 rounded-lg border border-white/10">
                        <span class="text-3xl font-bold text-white"><?php echo htmlspecialchars($question); ?> = ?</span>
                    </div>
                </div>
                <input 
                    type="number" 
                    name="challenge_response" 
                    required 
                    autofocus
                    class="w-full px-4 py-3 bg-black/50 border border-white/10 rounded-lg text-white text-center text-xl font-semibold focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    placeholder="Digite a resposta"
                    min="0"
                    max="100"
                >
            </div>

            <button 
                type="submit"
                class="w-full py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-lg shadow-blue-500/20"
            >
                Verificar e Continuar
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-zinc-500">
            <p>Protegido por <strong class="text-zinc-400">SafeNode</strong></p>
            <p class="mt-1">IP: <?php echo htmlspecialchars($ipAddress); ?></p>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Auto-focus no input
        document.querySelector('input[name="challenge_response"]').focus();
    </script>
</body>
</html>

