<?php
/**
 * SafeNode - Checkout Pix
 */

session_start();

if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/PixManager.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'];
$pixManager = new PixManager($db);

// Se já existe um pagamento pendente para este usuário, usar ele
$stmt = $db->prepare("SELECT * FROM safenode_payments WHERE user_id = ? AND status = 'PENDING' ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$existingPayment = $stmt->fetch();

$paymentData = null;
$amount = 29.00;

if ($existingPayment) {
    // Tentar recuperar os dados da API (ou do cache do banco se tivéssemos salvo o base64)
    // Para simplificar, vamos tentar gerar um novo ou usar o txid existente
    $txid = $existingPayment['txid'];
    // Em produção, você consultaria o status ou regeneraria o QR Code se necessário
} else {
    // Criar nova cobrança
    // Nota: Isso vai falhar se as credenciais da EFI não estiverem configuradas
    $paymentData = $pixManager->createImmediateCharge($userId, $amount);
}

// Fallback para desenvolvimento (se a API falhar por falta de credenciais)
$isSimulated = false;
if (!$paymentData && !$existingPayment) {
    $isSimulated = true;
    $txid = 'dev_simulated_' . bin2hex(random_bytes(4));
    $paymentData = [
        'txid' => $txid,
        'qrcode' => '00020126330014BR.GOV.BCB.PIX0111testchavetest520400005303986540529.005802BR5910SafeNode6009Sao Paulo62070503***6304ABCD',
        'imagem' => '' // Sem imagem real no simulado
    ];
}

$pageTitle = 'Checkout Pix';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Pix | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="includes/theme-styles.css">
    <script src="includes/theme-toggle.js"></script>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: { 950: '#030303', 900: '#050505', 800: '#0a0a0a' }
                    }
                }
            }
        }
    </script>

    <style>
        body { background-color: var(--bg-primary); color: var(--text-primary); }
        .glass { background: var(--glass-bg); backdrop-filter: blur(20px); border: 1px solid var(--border-subtle); }
        .gradient-text { background: linear-gradient(135deg, #3b82f6 0%, #22c55e 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4" x-data="checkout('<?php echo $txid; ?>')">
    <div class="w-full max-w-lg">
        <div class="glass rounded-3xl p-8 md:p-10 shadow-2xl relative overflow-hidden">
            <!-- Background Decoration -->
            <div class="absolute -top-24 -right-24 w-48 h-48 bg-blue-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-green-500/10 rounded-full blur-3xl"></div>

            <div class="relative z-10 space-y-8">
                <!-- Header -->
                <div class="text-center">
                    <img src="assets/img/logos (6).png" alt="SafeNode" class="w-12 h-12 mx-auto mb-4">
                    <h1 class="text-2xl font-extrabold tracking-tight">Assinatura Premium</h1>
                    <p class="text-zinc-500 text-sm mt-2">Plano Profissional • R$ 29,00 / mês</p>
                </div>

                <!-- QR Code Container -->
                <div class="bg-white dark:bg-white/5 p-6 rounded-2xl border border-gray-100 dark:border-white/10 flex flex-col items-center">
                    <?php if ($isSimulated): ?>
                        <div class="w-48 h-48 bg-gray-200 dark:bg-white/10 rounded-xl flex items-center justify-center mb-4 relative">
                            <i data-lucide="qr-code" class="w-24 h-24 text-zinc-400"></i>
                            <div class="absolute bottom-2 px-2 py-0.5 bg-amber-500/20 text-amber-500 text-[10px] font-bold rounded">MODO SIMULAÇÃO</div>
                        </div>
                    <?php elseif ($paymentData && !empty($paymentData['imagem'])): ?>
                        <img src="data:image/png;base64,<?php echo $paymentData['imagem']; ?>" class="w-48 h-48 rounded-xl mb-4" alt="QR Code Pix">
                    <?php else: ?>
                        <div class="w-48 h-48 bg-red-500/10 rounded-xl flex flex-col items-center justify-center mb-4 text-center p-4">
                            <i data-lucide="alert-circle" class="w-8 h-8 text-red-500 mb-2"></i>
                            <p class="text-[10px] text-red-500 font-bold uppercase">Erro de Conexão</p>
                            <p class="text-[9px] text-zinc-500 leading-tight">Credenciais EFI não configuradas.</p>
                        </div>
                    <?php endif; ?>

                    <div class="text-center space-y-2">
                        <div class="flex items-center justify-center gap-2 text-green-500 font-bold text-sm">
                            <i data-lucide="zap" class="w-4 h-4"></i>
                            Pagamento Instantâneo via Pix
                        </div>
                        <p class="text-xs text-zinc-500 max-w-[200px] mx-auto">Escaneie o QR Code acima com o app do seu banco para pagar.</p>
                    </div>
                </div>

                <!-- Copia e Cola -->
                <div class="space-y-3">
                    <label class="text-xs font-bold text-zinc-500 uppercase tracking-widest ml-1">Código Copia e Cola</label>
                    <div class="flex gap-2">
                        <input type="text" readonly value="<?php echo $paymentData['qrcode']; ?>" class="flex-1 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none overflow-hidden text-ellipsis">
                        <button @click="copyCode" class="bg-zinc-900 dark:bg-white text-white dark:text-black px-4 rounded-xl hover:opacity-90 transition-all flex items-center justify-center">
                            <i data-lucide="copy" class="w-4 h-4" x-show="!copied"></i>
                            <i data-lucide="check" class="w-4 h-4 text-green-500" x-show="copied"></i>
                        </button>
                    </div>
                </div>

                <!-- Status & Feedback -->
                <div class="flex items-center justify-between p-4 bg-blue-500/5 rounded-2xl border border-blue-500/10">
                    <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                        <div class="text-sm font-bold text-blue-500" x-text="statusText">Aguardando pagamento...</div>
                    </div>
                    <div class="text-xs font-mono text-zinc-500" x-text="timer">14:59</div>
                </div>

                <?php if ($isSimulated): ?>
                <button @click="simulateSuccess" class="w-full py-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-500 text-sm font-bold hover:bg-amber-500/20 transition-all">
                    Simular Pagamento Confirmado (Dev Only)
                </button>
                <?php endif; ?>

                <div class="pt-4 text-center">
                    <a href="dashboard.php" class="text-xs text-zinc-400 hover:text-zinc-600 transition-colors flex items-center justify-center gap-1">
                        <i data-lucide="arrow-left" class="w-3 h-3"></i>
                        Cancelar e voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function checkout(txid) {
            return {
                txid: txid,
                status: 'PENDING',
                statusText: 'Aguardando pagamento...',
                copied: false,
                timeLeft: 900, // 15 minutos
                timer: '15:00',
                polling: null,

                init() {
                    lucide.createIcons();
                    this.startTimer();
                    this.startPolling();
                },

                startTimer() {
                    setInterval(() => {
                        if (this.timeLeft > 0) {
                            this.timeLeft--;
                            const m = Math.floor(this.timeLeft / 60);
                            const s = this.timeLeft % 60;
                            this.timer = `${m}:${s < 10 ? '0' : ''}${s}`;
                        } else {
                            this.statusText = 'Tempo expirado';
                            clearInterval(this.polling);
                        }
                    }, 1000);
                },

                startPolling() {
                    this.polling = setInterval(async () => {
                        try {
                            const res = await fetch(`api/pix-status.php?txid=${this.txid}`);
                            const data = await res.json();
                            
                            if (data.status === 'COMPLETED') {
                                this.statusText = 'Pagamento Confirmado!';
                                clearInterval(this.polling);
                                setTimeout(() => window.location.href = 'dashboard.php?payment=success', 2000);
                            }
                        } catch (e) {
                            console.error('Polling error:', e);
                        }
                    }, 3000); // Poll a cada 3 segundos
                },

                copyCode() {
                    const input = document.querySelector('input[readonly]');
                    input.select();
                    document.execCommand('copy');
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                },

                simulateSuccess() {
                    fetch(`api/pix-callback.php?simulated_txid=${this.txid}`)
                        .then(() => {
                            this.statusText = 'Simulando Sucesso...';
                        });
                }
            }
        }
    </script>
</body>
</html>
