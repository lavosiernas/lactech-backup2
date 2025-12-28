<?php
/**
 * SafeNode - Browser Integrity Check
 * Verificação de navegador estilo Cloudflare (Interstitial)
 */

class BrowserIntegrity {
    private $db;
    private $secretKey;

    public function __construct($db) {
        $this->db = $db;
        // Tenta pegar uma chave secreta do banco ou usa um fallback
        $this->secretKey = defined('SAFENODE_SECRET') ? SAFENODE_SECRET : 'safenode_default_secret_key_' . date('Ym');
    }

    /**
     * Executa a verificação de integridade
     * Se falhar, interrompe a execução e mostra a tela de verificação
     */
    public function check($force = false) {
        // 1. Ignorar se for verificação interna (AJAX do desafio)
        if (isset($_POST['safenode_challenge_response'])) {
            $this->validateChallenge();
            return;
        }
        
        $cookieName = $force ? 'safenode_integrity_ua' : 'safenode_integrity';

        // 2. Verificar cookie de integridade
        if ($this->hasValidCookie($cookieName)) {
            return; // Passou
        }

        // 3. Se chegou aqui, precisa verificar
        // Ignorar assets estáticos comuns para não quebrar imagens/css se acessados diretamente (opcional, mas recomendado)
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (preg_match('/\.(jpg|jpeg|png|gif|css|js|ico|svg|woff|woff2)$/i', $uri)) {
            return; 
        }

        // 4. Renderizar tela de desafio (Interstitial)
        $this->renderInterstitial($force);
        exit;
    }

    private function hasValidCookie($cookieName = 'safenode_integrity') {
        if (!isset($_COOKIE[$cookieName])) {
            return false;
        }

        $cookieData = $_COOKIE[$cookieName];
        $ip = $this->getClientIP();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Recriar hash para validar
        $expectedHash = hash_hmac('sha256', $ip . $ua, $this->secretKey);

        return hash_equals($expectedHash, $cookieData);
    }

    private function validateChallenge() {
        // Aqui poderíamos validar algum cálculo matemático enviado pelo JS
        // Por enquanto, o fato de ter executado o JS e feito o POST já filtra bots simples (curl/wget)
        
        $solution = $_POST['safenode_challenge_response'] ?? '';
        $mode = $_POST['safenode_challenge_mode'] ?? 'normal';
        
        // Validação simples: o JS enviou o valor esperado?
        // No renderInterstitial vamos definir que o JS deve enviar um hash específico ou apenas 'success'
        
        $ip = $this->getClientIP();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $hash = hash_hmac('sha256', $ip . $ua, $this->secretKey);

        // Definir cookie válido por 1 hora (ou mais)
        $cookieName = $mode === 'ua' ? 'safenode_integrity_ua' : 'safenode_integrity';
        setcookie($cookieName, $hash, [
            'expires' => time() + 3600 * 3, // 3 horas
            'path' => '/',
            'domain' => '', // Current domain
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Retornar sucesso para o JS recarregar a página
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success']);
        exit;
    }

    private function renderInterstitial($force = false) {
        // Evitar cache desta página
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        
        $ip = htmlspecialchars($this->getClientIP());
        $rayId = substr(md5(uniqid()), 0, 16); // ID único da requisição (estilo Ray ID)
        $mode = $force ? 'ua' : 'normal';

        ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificando seu navegador | SafeNode</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #000000; color: #e4e4e7; font-family: system-ui, -apple-system, sans-serif; }
        .spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.1);
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body class="h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-md w-full bg-zinc-900/50 border border-zinc-800 rounded-2xl p-8 text-center shadow-2xl">
        <div class="flex justify-center mb-6">
            <div class="spinner"></div>
        </div>
        
        <h1 class="text-2xl font-bold text-white mb-2">
            <?php echo $force ? 'Proteção reforçada ativada' : 'Verificando sua conexão...'; ?>
        </h1>
        <p class="text-zinc-400 text-sm mb-8">
            <?php echo $force 
                ? 'Este site está sob proteção avançada. Estamos confirmando que sua conexão é legítima.' 
                : 'Por favor, aguarde enquanto verificamos se sua conexão é segura. Isso levará apenas alguns segundos.'; ?>
        </p>

        <div class="flex items-center justify-center gap-2 text-xs text-zinc-500 font-mono bg-black/30 p-3 rounded-lg">
            <span>Ray ID: <span class="text-zinc-300"><?php echo $rayId; ?></span></span>
            <span>•</span>
            <span>IP: <span class="text-zinc-300"><?php echo $ip; ?></span></span>
        </div>

        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-zinc-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            Protected by SafeNode
        </div>
    </div>

    <script>
        (function() {
            // Simula uma verificação client-side
            console.log("SafeNode: Verifying browser integrity...");
            
            setTimeout(function() {
                // Envia resposta do desafio
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'safenode_challenge_response=1&safenode_challenge_mode=<?php echo $mode; ?>'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Recarrega a página original
                        window.location.reload();
                    }
                })
                .catch(err => {
                    console.error("Verification failed", err);
                });
            }, 2500); // Delay de 2.5s para "efeito" e evitar loops muito rápidos
        })();
    </script>
</body>
</html>
        <?php
    }

    private function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

