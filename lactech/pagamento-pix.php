<?php
/**
 * Página de Pagamento PIX - LacPay
 * Página dedicada para processamento de pagamentos via PIX
 */

// Processar parâmetros da URL
$planId = $_GET['plan'] ?? 'basico';
$planName = $_GET['name'] ?? 'Básico';
$planValue = floatval($_GET['value'] ?? 199);

// Mapeamento de planos
$plans = [
    'basico' => [
        'name' => 'Básico',
        'value' => 199,
        'description' => 'Plano ideal para pequenas fazendas'
    ],
    'profissional' => [
        'name' => 'Profissional',
        'value' => 399,
        'description' => 'Plano completo para fazendas médias'
    ],
    'empresarial' => [
        'name' => 'Empresarial',
        'value' => 799,
        'description' => 'Solução empresarial para grandes operações'
    ]
];

// Validar plano
if (!isset($plans[$planId])) {
    header('Location: index.php');
    exit;
}

$selectedPlan = $plans[$planId];
$planName = $selectedPlan['name'];
$planValue = $selectedPlan['value'];
?>
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - LacPay | LacTech</title>
    <meta name="description" content="Realize o pagamento do seu plano LacTech de forma segura via PIX">
    <link rel="icon" href="./assets/img/lactech-logo.png" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- QRCode.js - Carregar com múltiplos fallbacks -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script>
        // Verificar se QRCode carregou, se não usar fallback
        if (typeof QRCode === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js';
            document.head.appendChild(script);
        }
    </script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#22c55e',
                        'primary-dark': '#16a34a'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        * {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
            color: #1a1a1a;
            font-size: 16px;
            line-height: 1.6;
        }

        .payment-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .payment-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .payment-card:hover {
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.12);
        }

        .qr-code-container {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
            border: 2px dashed #22c55e;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
        }

        .qr-code-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(34, 197, 94, 0.03) 10px,
                rgba(34, 197, 94, 0.03) 20px
            );
            animation: slide 20s linear infinite;
        }

        @keyframes slide {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .pix-code-input {
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .pix-code-input:focus {
            border-color: #22c55e;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .feature-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            box-shadow: 0 4px 14px rgba(34, 197, 94, 0.25);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .copy-btn {
            transition: all 0.3s ease;
        }

        .copy-btn.copied {
            background: #16a34a;
        }

        .loading-spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #22c55e;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .security-badge {
            background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);
            border: 1px solid #dcfce7;
        }

        @media (max-width: 768px) {
            .payment-container {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="index.php" class="flex items-center space-x-3">
                    <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="w-10 h-10">
                    <span class="text-2xl font-bold text-gray-800">
                        <span>Lac</span><span class="text-green-600">Tech</span>
                    </span>
                </a>
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <span>Pagamento seguro</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-screen py-12">
        <div class="payment-container px-4 sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-8 max-w-2xl mx-auto">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-semibold">
                            1
                        </div>
                        <span class="text-sm font-medium text-gray-700">Plano selecionado</span>
                    </div>
                    <div class="flex-1 h-1 bg-green-600 mx-4"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center text-white font-semibold">
                            2
                        </div>
                        <span class="text-sm font-medium text-gray-700">Pagamento</span>
                    </div>
                    <div class="flex-1 h-1 bg-gray-200 mx-4"></div>
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 font-semibold">
                            3
                        </div>
                        <span class="text-sm font-medium text-gray-500">Confirmação</span>
                    </div>
                </div>
            </div>

            <!-- Payment Grid -->
            <div class="grid lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Left Column: Payment Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Payment Card -->
                    <div class="payment-card p-8">
                        <div class="flex items-center justify-between mb-6">
                            <h1 class="text-3xl font-bold text-gray-900">Finalizar Pagamento</h1>
                            <div class="flex items-center space-x-2 px-4 py-2 bg-green-50 rounded-full">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-semibold text-green-700">LacPay - PIX</span>
                            </div>
                        </div>

                        <!-- QR Code Section -->
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Escaneie o QR Code</h2>
                            <div class="qr-code-container p-8 flex flex-col items-center justify-center">
                                <div id="qrCodeDisplay" class="w-64 h-64 bg-white rounded-xl flex items-center justify-center mb-4 relative z-10">
                                    <!-- QR Code será gerado automaticamente -->
                                    <div class="text-center text-gray-400">
                                        <div class="loading-spinner mx-auto mb-4"></div>
                                        <p class="text-sm font-medium">Gerando QR Code...</p>
                                        <p class="text-xs mt-2">Aguarde alguns instantes</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 text-center relative z-10">
                                    Abra o app do seu banco e escaneie o código para pagar
                                </p>
                            </div>
                        </div>

                        <!-- PIX Code Input -->
                        <div class="mb-8">
                            <label class="block text-sm font-semibold text-gray-900 mb-3">
                                Ou copie o código PIX
                            </label>
                            <div class="flex space-x-3">
                                <input 
                                    type="text" 
                                    id="pixCodeInput" 
                                    readonly
                                    value="Aguardando geração do código PIX..."
                                    class="pix-code-input flex-1 px-4 py-4 rounded-xl text-sm focus:outline-none"
                                >
                                <button 
                                    onclick="copyPixCode()" 
                                    id="copyBtn"
                                    class="copy-btn px-6 py-4 bg-green-600 text-white rounded-xl hover:bg-green-700 font-semibold transition-all flex items-center space-x-2 whitespace-nowrap"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                    <span>Copiar</span>
                                </button>
                            </div>
                        </div>

                        <!-- Transaction Info -->
                        <div class="bg-gray-50 rounded-xl p-6 mb-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Informações da Transação</h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Plano:</span>
                                    <span class="text-sm font-semibold text-gray-900" id="transactionPlan"><?php echo htmlspecialchars($planName); ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">TXID:</span>
                                    <span class="text-xs font-mono text-gray-700" id="transactionTxid">Aguardando geração...</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-600">Status:</span>
                                    <span class="status-badge bg-yellow-100 text-yellow-800" id="transactionStatus">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Aguardando pagamento
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="space-y-4">
                            <button 
                                onclick="regeneratePayment()" 
                                id="regenerateBtn"
                                class="w-full px-6 py-4 border-2 border-green-600 text-green-600 rounded-xl hover:bg-green-50 font-semibold transition-all flex items-center justify-center space-x-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Regenerar QR Code</span>
                            </button>
                            <a 
                                href="index.php#pricing" 
                                class="block w-full px-6 py-4 border-2 border-gray-300 text-gray-800 rounded-xl hover:bg-gray-50 text-center font-semibold transition-all"
                            >
                                Voltar para Planos
                            </a>
                        </div>

                        <!-- Instructions -->
                        <div class="mt-8 bg-green-50 border border-green-200 rounded-xl p-6">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Como pagar com PIX
                            </h4>
                            <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                                <li>Clique em "Gerar Cobrança PIX" para criar sua cobrança</li>
                                <li>Abra o app do seu banco e selecione a opção PIX</li>
                                <li>Escaneie o QR Code exibido ou cole o código PIX</li>
                                <li>Confirme o valor e finalize o pagamento</li>
                                <li>O pagamento será confirmado após verificação no nosso sistema</li>
                            </ol>
                        </div>
                    </div>

                    <!-- Security Badge -->
                    <div class="security-badge rounded-xl p-6">
                        <div class="flex items-start space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-900 mb-1">Pagamento 100% Seguro</h4>
                                <p class="text-xs text-gray-600">
                                    Seus dados são protegidos com criptografia de ponta. Não armazenamos informações sensíveis do pagamento.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="lg:col-span-1">
                    <div class="payment-card p-6 sticky top-24">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Resumo do Pedido</h2>
                        
                        <!-- Plan Info -->
                        <div class="mb-6 pb-6 border-b border-gray-200">
                            <div class="flex items-center space-x-4 mb-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center text-white font-bold text-lg">
                                    <?php echo strtoupper(substr($planName, 0, 1)); ?>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($planName); ?></h3>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($selectedPlan['description']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Features List -->
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Incluído no plano</h4>
                            <div class="space-y-0">
                                <?php if ($planId === 'basico'): ?>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Até 50 animais</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Controle de produção</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Relatórios básicos</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Suporte por email</span>
                                    </div>
                                <?php elseif ($planId === 'profissional'): ?>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Até 200 animais</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Todas as funcionalidades</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Relatórios avançados</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Suporte prioritário</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">App mobile</span>
                                    </div>
                                <?php else: ?>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Animais ilimitados</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Múltiplas fazendas</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">API personalizada</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Suporte 24/7</span>
                                    </div>
                                    <div class="feature-item">
                                        <svg class="w-5 h-5 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <span class="text-sm text-gray-700">Consultoria incluída</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="bg-gray-50 rounded-xl p-4 space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="text-gray-900 font-medium">R$ <?php echo number_format($planValue, 2, ',', '.'); ?></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">Taxa PIX:</span>
                                <span class="text-green-600 font-medium">Grátis</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 mt-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-gray-900">Total:</span>
                                    <span class="text-2xl font-bold text-green-600">R$ <?php echo number_format($planValue, 2, ',', '.'); ?></span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Pagamento único mensal</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-100 mt-16">
        <div class="max-w-7xl mx-auto px-6 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                <div class="flex items-center space-x-3">
                    <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="w-8 h-8">
                    <span class="text-gray-600 text-sm">© 2025 LacTech. Todos os direitos reservados.</span>
                </div>
                <div class="flex items-center space-x-6 text-sm text-gray-600">
                    <a href="index.php" class="hover:text-green-600 transition-colors">Voltar ao site</a>
                    <a href="politica-privacidade.php" class="hover:text-green-600 transition-colors">Privacidade</a>
                    <a href="termos-condicoes.php" class="hover:text-green-600 transition-colors">Termos</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Handler global de erros não capturados (especialmente de extensões)
        window.addEventListener('error', function(event) {
            // Ignorar erros de extensões do navegador
            if (event.message && (
                event.message.includes('message channel') ||
                event.message.includes('Extension context') ||
                event.message.includes('chrome-extension')
            )) {
                event.preventDefault();
                return false;
            }
        });

        // Handler para promessas rejeitadas não tratadas
        window.addEventListener('unhandledrejection', function(event) {
            // Ignorar erros de extensões do navegador
            if (event.reason && event.reason.message && (
                event.reason.message.includes('message channel') ||
                event.reason.message.includes('Extension context') ||
                event.reason.message.includes('chrome-extension')
            )) {
                event.preventDefault();
                return false;
            }
        });

        // Dados do plano
        const planData = {
            id: '<?php echo $planId; ?>',
            name: '<?php echo htmlspecialchars($planName, ENT_QUOTES); ?>',
            value: <?php echo $planValue; ?>
        };

        // Chave PIX do recebedor (não deve ser exposta no frontend em produção)
        const PIX_KEY = 'slavosier298@gmail.com';
        const PIX_RECEIVER_NAME = 'LacTech - Sistema de Gestão Leiteira';
        const PIX_CITY = 'Brasília';

        // Função para gerar TXID único
        function generateTxid() {
            const timestamp = Date.now();
            const random = Math.random().toString(36).substring(2, 9).toUpperCase();
            return 'LACPIX' + timestamp + random;
        }

        // Função para gerar payload PIX (BR Code)
        function generatePixPayload(txid, value, description) {
            try {
                // Formato BR Code do PIX conforme especificação do Banco Central
                // Cada campo segue o padrão: ID(2) + tamanho(2) + valor
                
                // 1. Payload Format Indicator (00) = 01
                let payload = '000201';
                
                // 2. Point of Initiation Method (01) = 12 (chave estática reutilizável)
                payload += '010212';
                
                // 3. Merchant Account Information (26)
                // Subcampo 00: GUI (Global Unique Identifier) = br.gov.bcb.pix
                const gui = '0014br.gov.bcb.pix';
                
                // Subcampo 01: Chave PIX (email) - máximo 77 caracteres
                const pixKey = PIX_KEY.trim();
                if (!pixKey || pixKey.length === 0) {
                    throw new Error('Chave PIX não definida');
                }
                const pixKeyLength = String(pixKey.length).padStart(2, '0');
                const pixKeyField = '01' + pixKeyLength + pixKey;
                
                // Comprimento total do campo 26
                const merchantAccountInfo = gui + pixKeyField;
                const merchantAccountInfoLength = String(merchantAccountInfo.length).padStart(2, '0');
                payload += '26' + merchantAccountInfoLength + merchantAccountInfo;
                
                // 4. Merchant Category Code (52) = 0000
                payload += '52040000';
                
                // 5. Transaction Currency (53) = 986 (BRL)
                payload += '5303986';
                
                // 6. Transaction Amount (54) - obrigatório quando há valor
                const numValue = parseFloat(value);
                if (isNaN(numValue) || numValue <= 0) {
                    throw new Error('Valor inválido');
                }
                const amount = numValue.toFixed(2);
                const amountLength = String(amount.length).padStart(2, '0');
                payload += '54' + amountLength + amount;
                
                // 7. Country Code (58) = BR
                payload += '5802BR';
                
                // 8. Merchant Name (59) - máximo 25 caracteres
                const merchantName = PIX_RECEIVER_NAME.substring(0, 25).trim();
                if (merchantName.length === 0) {
                    throw new Error('Nome do recebedor não pode estar vazio');
                }
                const merchantNameLength = String(merchantName.length).padStart(2, '0');
                payload += '59' + merchantNameLength + merchantName;
                
                // 9. Merchant City (60) - máximo 15 caracteres
                const city = PIX_CITY.substring(0, 15).trim();
                if (city.length === 0) {
                    throw new Error('Cidade não pode estar vazia');
                }
                const cityLength = String(city.length).padStart(2, '0');
                payload += '60' + cityLength + city;
                
                // 10. Additional Data Field Template (62) - opcional
                // Subcampo 05: Reference Label (TXID) - máximo 25 caracteres
                const txidFormatted = txid.substring(0, 25).trim();
                if (txidFormatted.length > 0) {
                    const txidLength = String(txidFormatted.length).padStart(2, '0');
                    const txidField = '05' + txidLength + txidFormatted;
                    const additionalDataLength = String(txidField.length).padStart(2, '0');
                    payload += '62' + additionalDataLength + txidField;
                }
                
                // 11. Calcular CRC16 sobre todo o payload (antes de adicionar o campo 63)
                const crc = calculateCRC16(payload);
                const crcHex = crc.toString(16).toUpperCase().padStart(4, '0');
                
                // 12. Adicionar CRC16 (63) - sempre o último campo
                payload += '6304' + crcHex;
                
                // Validar tamanho máximo do payload (512 caracteres)
                if (payload.length > 512) {
                    throw new Error('Payload PIX excede tamanho máximo');
                }
                
                return payload;
            } catch (error) {
                console.error('Erro ao gerar payload PIX:', error);
                throw error;
            }
        }

        // Função para calcular CRC16 (padrão PIX - CRC-16/CCITT-FALSE)
        function calculateCRC16(str) {
            const polynomial = 0x1021;
            let crc = 0xFFFF;
            
            // Processar cada byte da string
            for (let i = 0; i < str.length; i++) {
                const byte = str.charCodeAt(i);
                crc ^= (byte << 8);
                
                for (let bit = 0; bit < 8; bit++) {
                    if (crc & 0x8000) {
                        crc = ((crc << 1) ^ polynomial) & 0xFFFF;
                    } else {
                        crc = (crc << 1) & 0xFFFF;
                    }
                }
            }
            
            return crc & 0xFFFF;
        }

        function generatePayment() {
            const regenerateBtn = document.getElementById('regenerateBtn');
            const originalHTML = regenerateBtn ? regenerateBtn.innerHTML : '';
            
            // Mostrar loading no botão se existir
            if (regenerateBtn) {
                regenerateBtn.disabled = true;
                regenerateBtn.innerHTML = `
                    <div class="loading-spinner mr-2"></div>
                    <span>Gerando cobrança...</span>
                `;
            }

            // Gerar cobrança via backend (o backend gera o código PIX)
            generatePaymentFromBackend();
        }
        
        // Código PIX fixo para plano básico (teste)
        const PIX_CODE_BASICO_FIXO = '00020126580014BR.GOV.BCB.PIX0136ebabf96f-5162-4bd1-95c5-64ffa8e9bfed52040000530398654041.995802BR5924Francisco Lavosier Silva6009SAO PAULO62140510sfR94Rftgb6304A197';
        
        // Função para gerar pagamento via backend
        async function generatePaymentFromBackend() {
            const regenerateBtn = document.getElementById('regenerateBtn');
            const originalHTML = regenerateBtn ? regenerateBtn.innerHTML : '';
            
            // Mostrar loading no botão se existir
            if (regenerateBtn) {
                regenerateBtn.disabled = true;
                regenerateBtn.innerHTML = `
                    <div class="loading-spinner mr-2"></div>
                    <span>Gerando cobrança...</span>
                `;
            }
            
            try {
                // Se for plano básico, usar código PIX fixo
                if (planData.id === 'basico') {
                    console.log('=== PLANO BÁSICO - USANDO CÓDIGO FIXO ===');
                    const fixedTxid = 'BASICO-FIXO-' + Date.now();
                    const pixCode = PIX_CODE_BASICO_FIXO;
                    
                    console.log('TXID:', fixedTxid);
                    console.log('Código PIX fixo:', pixCode);
                    console.log('Tamanho:', pixCode.length);
                    
                    // Atualizar interface
                    updatePaymentInfo({
                        txid: fixedTxid,
                        pixCode: pixCode,
                        qrCode: pixCode
                    });
                    
                    // Gerar QR Code
                    console.log('Iniciando geração do QR Code...');
                    setTimeout(() => {
                        renderQRCode(pixCode);
                    }, 100);
                    
                    // Registrar no backend (opcional)
                    try {
                        await fetch('api/lacpay.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                txid: fixedTxid,
                                plan_id: planData.id,
                                plan_name: planData.name,
                                plan_value: planData.value,
                                pix_code: pixCode
                            })
                        });
                    } catch (e) {
                        console.log('Erro ao registrar no backend (não crítico):', e);
                    }
                    
                    // Iniciar verificação em tempo real
                    startPaymentVerification(fixedTxid);
                    
                    // Restaurar botão se existir
                    if (regenerateBtn && originalHTML) {
                        regenerateBtn.innerHTML = originalHTML;
                        regenerateBtn.disabled = false;
                    }
                    return;
                }
                
                // Para outros planos, usar backend normal
                const response = await fetch('api/lacpay.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        plan_id: planData.id,
                        plan_name: planData.name,
                        plan_value: planData.value
                    })
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    throw new Error(result.message || 'Erro ao gerar cobrança PIX');
                }
                
                const { txid, pix_code } = result.data;
                
                console.log('=== COBRANÇA GERADA COM SUCESSO ===');
                console.log('TXID:', txid);
                console.log('Código PIX:', pix_code);
                console.log('Tamanho:', pix_code.length);
                console.log('===============================');
                
                // Atualizar interface
                updatePaymentInfo({
                    txid: txid,
                    pixCode: pix_code,
                    qrCode: pix_code
                });
                
                // Gerar QR Code
                console.log('Iniciando geração do QR Code...');
                setTimeout(() => {
                    renderQRCode(pix_code);
                }, 100);
                
                // Iniciar verificação em tempo real
                startPaymentVerification(txid);
                
                // Restaurar botão se existir
                if (regenerateBtn && originalHTML) {
                    regenerateBtn.innerHTML = originalHTML;
                    regenerateBtn.disabled = false;
                }
                
            } catch (error) {
                console.error('Erro ao gerar cobrança:', error);
                
                // Mostrar erro na interface
                const qrContainer = document.getElementById('qrCodeDisplay');
                if (qrContainer) {
                    qrContainer.innerHTML = `
                        <div class="text-center text-red-500 p-4">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm font-medium">Erro ao gerar cobrança</p>
                            <p class="text-xs mt-1">${error.message}</p>
                            <button onclick="generatePayment()" class="mt-3 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                                Tentar novamente
                            </button>
                        </div>
                    `;
                }
                
                // Restaurar botão se existir
                if (regenerateBtn && originalHTML) {
                    regenerateBtn.innerHTML = originalHTML;
                    regenerateBtn.disabled = false;
                }
            }
        }

        function updatePaymentInfo(data) {
            console.log('Atualizando informações de pagamento:', data);
            
            // Atualizar TXID
            if (document.getElementById('transactionTxid')) {
                document.getElementById('transactionTxid').textContent = data.txid || 'Aguardando...';
            }
            
            // Atualizar código PIX
            if (document.getElementById('pixCodeInput')) {
                document.getElementById('pixCodeInput').value = data.pixCode || 'Aguardando geração...';
            }
            
            // Renderizar QR Code apenas se ainda não foi renderizado
            // (evitar chamadas duplicadas)
            if (data.qrCode && data.pixCode) {
                const qrContainer = document.getElementById('qrCodeDisplay');
                if (qrContainer && (!qrContainer.querySelector('img') && !qrContainer.querySelector('canvas'))) {
                    console.log('Renderizando QR Code via updatePaymentInfo...');
                    renderQRCode(data.pixCode);
                }
            }
            
            // Atualizar status
            const statusEl = document.getElementById('transactionStatus');
            if (statusEl) {
                statusEl.className = 'status-badge bg-yellow-100 text-yellow-800';
                statusEl.innerHTML = `
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aguardando pagamento
                `;
            }
        }

        function renderQRCode(pixCode) {
            console.log('=== INICIANDO RENDERIZAÇÃO QR CODE ===');
            console.log('Código PIX:', pixCode ? pixCode.substring(0, 50) + '...' : 'VAZIO');
            
            const qrContainer = document.getElementById('qrCodeDisplay');
            
            if (!qrContainer) {
                console.error('ERRO: Container QR Code não encontrado!');
                return;
            }
            
            if (!pixCode || pixCode.length < 10) {
                console.error('ERRO: Código PIX inválido!');
                qrContainer.innerHTML = `
                    <div class="text-center text-red-500 p-4">
                        <p class="text-sm">Código PIX inválido</p>
                    </div>
                `;
                return;
            }
            
            // Limpar e mostrar loading
            qrContainer.innerHTML = '<div class="loading-spinner mx-auto"></div>';
            
            // ESTRATÉGIA: Usar API externa primeiro (mais confiável)
            console.log('Tentando método 1: API externa qrserver.com');
            generateQRCodeWithAPI(qrContainer, pixCode);
        }

        // Gerar QR Code usando API externa
        function generateQRCodeWithAPI(container, text) {
            console.log('Iniciando geração via API externa...');
            
            // Primeira opção: qrserver.com (funciona em HTTP e HTTPS)
            const qrApiUrl1 = `https://api.qrserver.com/v1/create-qr-code/?size=256x256&margin=2&data=${encodeURIComponent(text)}`;
            
            const img1 = document.createElement('img');
            img1.crossOrigin = 'anonymous';
            img1.src = qrApiUrl1;
            img1.alt = 'QR Code PIX';
            img1.className = 'w-full h-full object-contain';
            img1.style.display = 'block';
            img1.style.maxWidth = '100%';
            img1.style.height = 'auto';
            
            // Timeout para detectar se a imagem não carregou
            let loadTimeout = setTimeout(() => {
                if (!img1.complete || img1.naturalHeight === 0) {
                    console.log('✗ Timeout: qrserver.com demorou muito, tentando Google Charts...');
                    img1.onerror();
                }
            }, 5000);
            
            img1.onload = function() {
                clearTimeout(loadTimeout);
                console.log('✓ SUCESSO: QR Code gerado via qrserver.com');
                container.innerHTML = '';
                container.appendChild(img1);
            };
            
            img1.onerror = function() {
                clearTimeout(loadTimeout);
                console.log('✗ Falhou: qrserver.com, tentando Google Charts...');
                
                // Segunda opção: Google Charts
                const qrApiUrl2 = `https://chart.googleapis.com/chart?chs=256x256&cht=qr&chl=${encodeURIComponent(text)}`;
                
                const img2 = document.createElement('img');
                img2.crossOrigin = 'anonymous';
                img2.src = qrApiUrl2;
                img2.alt = 'QR Code PIX';
                img2.className = 'w-full h-full object-contain';
                img2.style.display = 'block';
                img2.style.maxWidth = '100%';
                img2.style.height = 'auto';
                
                let loadTimeout2 = setTimeout(() => {
                    if (!img2.complete || img2.naturalHeight === 0) {
                        console.log('✗ Timeout: Google Charts demorou muito, tentando QRCode.js...');
                        img2.onerror();
                    }
                }, 5000);
                
                img2.onload = function() {
                    clearTimeout(loadTimeout2);
                    console.log('✓ SUCESSO: QR Code gerado via Google Charts');
                    container.innerHTML = '';
                    container.appendChild(img2);
                };
                
                img2.onerror = function() {
                    clearTimeout(loadTimeout2);
                    console.log('✗ Falhou: Google Charts, tentando QRCode.js...');
                    // Terceira opção: QRCode.js (biblioteca local)
                    tryGenerateQRCodeLocal(container, text);
                };
                
                container.innerHTML = '';
                container.appendChild(img2);
            };
            
            container.innerHTML = '';
            container.appendChild(img1);
        }

        // Gerar QR Code usando biblioteca local QRCode.js
        function tryGenerateQRCodeLocal(container, pixCode) {
            // Verificar se QRCode.js está disponível
            function attemptLocalGeneration(retry = 0) {
                if (typeof QRCode !== 'undefined' && typeof QRCode.toCanvas === 'function') {
                    console.log('✓ QRCode.js disponível, gerando localmente...');
                    
                    const canvas = document.createElement('canvas');
                    canvas.className = 'w-full h-full object-contain';
                    canvas.style.display = 'block';
                    
                    try {
                        QRCode.toCanvas(canvas, pixCode, {
                            width: 256,
                            margin: 2,
                            color: {
                                dark: '#000000',
                                light: '#FFFFFF'
                            },
                            errorCorrectionLevel: 'M'
                        }, function (error) {
                            if (error) {
                                console.error('✗ ERRO ao gerar QR Code localmente:', error);
                                showQRCodeError(container);
                            } else {
                                console.log('✓ SUCESSO: QR Code gerado localmente');
                                container.innerHTML = '';
                                container.appendChild(canvas);
                            }
                        });
                    } catch (error) {
                        console.error('✗ ERRO na tentativa local:', error);
                        showQRCodeError(container);
                    }
                } else if (retry < 20) {
                    // QRCode.js ainda não carregou, esperar mais um pouco
                    console.log(`Esperando QRCode.js carregar... (tentativa ${retry + 1}/20)`);
                    setTimeout(() => attemptLocalGeneration(retry + 1), 500);
                } else {
                    console.error('✗ TIMEOUT: QRCode.js não carregou após 10 segundos');
                    showQRCodeError(container);
                }
            }
            
            attemptLocalGeneration();
        }

        // Mostrar erro e opção de copiar código
        function showQRCodeError(container) {
            console.log('⚠️ Mostrando fallback: QR Code não disponível');
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <svg class="w-12 h-12 text-yellow-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <p class="text-sm text-yellow-800 font-semibold mb-1">QR Code não disponível</p>
                        <p class="text-xs text-yellow-700">Use o código PIX abaixo para pagar</p>
                    </div>
                    <button onclick="document.getElementById('pixCodeInput').focus(); document.getElementById('pixCodeInput').select(); copyPixCode();" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">
                        Copiar Código PIX
                    </button>
                </div>
            `;
        }

        // Variáveis globais para polling
        let paymentCheckInterval = null;
        let currentTxid = null;

        // Salvar pagamento no backend
        // Função removida - o backend já salva quando gera o código PIX
        async function _unused_savePaymentToBackend(txid, pixCode) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 segundos timeout
                
                const response = await fetch('api/lacpay.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        txid: txid,
                        plan_id: planData.id,
                        plan_name: planData.name,
                        plan_value: planData.value,
                        pix_code: pixCode
                    }),
                    signal: controller.signal
                });

                clearTimeout(timeoutId);
                const result = await response.json();
                
                if (!result.success) {
                    console.error('Erro ao salvar pagamento:', result.message);
                } else {
                    console.log('✓ Pagamento salvo no backend com sucesso');
                }
            } catch (error) {
                // Ignorar erros de extensões do navegador
                if (error.name === 'AbortError') {
                    console.warn('⚠️ Timeout ao salvar pagamento no backend');
                } else if (error.message && error.message.includes('message channel')) {
                    // Erro de extensão do navegador - ignorar silenciosamente
                    console.warn('⚠️ Extensão do navegador interferiu na requisição (pode ser ignorado)');
                } else {
                    console.error('Erro ao salvar pagamento no backend:', error);
                }
            }
        }

        // Verificar status do pagamento
        async function checkPaymentStatus(txid) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 8000); // 8 segundos timeout
                
                const response = await fetch(`api/lacpay.php?txid=${encodeURIComponent(txid)}`, {
                    signal: controller.signal,
                    cache: 'no-cache'
                });
                
                clearTimeout(timeoutId);
                const result = await response.json();

                if (result.success && result.data) {
                    const payment = result.data;
                    updatePaymentStatusUI(payment);

                    // Se pagamento foi confirmado, parar polling
                    if (payment.status === 'pago') {
                        stopPaymentVerification();
                        showPaymentSuccess();
                    } else if (payment.status === 'expirado' || payment.status === 'cancelado') {
                        stopPaymentVerification();
                    }
                }
            } catch (error) {
                // Ignorar erros de extensões do navegador
                if (error.name === 'AbortError') {
                    console.warn('⚠️ Timeout ao verificar status do pagamento');
                } else if (error.message && error.message.includes('message channel')) {
                    // Erro de extensão do navegador - ignorar silenciosamente
                    // Não precisa logar isso pois é problema da extensão, não do nosso código
                    return;
                } else {
                    console.error('Erro ao verificar status:', error);
                }
            }
        }

        // Iniciar verificação periódica
        function startPaymentVerification(txid) {
            currentTxid = txid;
            
            // Verificar imediatamente
            checkPaymentStatus(txid);
            
            // Verificar a cada 10 segundos
            paymentCheckInterval = setInterval(() => {
                if (currentTxid) {
                    checkPaymentStatus(currentTxid);
                }
            }, 10000); // 10 segundos

            // Limpar intervalo após 30 minutos (tempo máximo de espera)
            setTimeout(() => {
                if (paymentCheckInterval) {
                    stopPaymentVerification();
                    showPaymentExpired();
                }
            }, 30 * 60 * 1000); // 30 minutos
        }

        // Parar verificação
        function stopPaymentVerification() {
            if (paymentCheckInterval) {
                clearInterval(paymentCheckInterval);
                paymentCheckInterval = null;
            }
            currentTxid = null;
        }

        // Atualizar UI com status do pagamento
        function updatePaymentStatusUI(payment) {
            const statusEl = document.getElementById('transactionStatus');
            
            switch (payment.status) {
                case 'pago':
                    statusEl.className = 'status-badge bg-green-100 text-green-800';
                    statusEl.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Pagamento confirmado
                    `;
                    break;
                case 'expirado':
                    statusEl.className = 'status-badge bg-red-100 text-red-800';
                    statusEl.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Expirado
                    `;
                    break;
                case 'cancelado':
                    statusEl.className = 'status-badge bg-gray-100 text-gray-800';
                    statusEl.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancelado
                    `;
                    break;
                default:
                    statusEl.className = 'status-badge bg-yellow-100 text-yellow-800';
                    statusEl.innerHTML = `
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Aguardando pagamento
                    `;
            }
        }

        // Mostrar mensagem de sucesso
        function showPaymentSuccess() {
            // Criar modal de sucesso
            const successModal = document.createElement('div');
            successModal.className = 'fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm';
            successModal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8 text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Pagamento Confirmado!</h3>
                    <p class="text-gray-600 mb-6">Seu pagamento foi verificado com sucesso. Obrigado!</p>
                    <button onclick="window.location.href='index.php'" class="w-full px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all font-semibold">
                        Voltar ao Site
                    </button>
                </div>
            `;
            document.body.appendChild(successModal);
            
            // Fechar modal ao clicar fora
            successModal.addEventListener('click', (e) => {
                if (e.target === successModal) {
                    successModal.remove();
                }
            });
        }

        // Mostrar mensagem de expirado
        function showPaymentExpired() {
            const statusEl = document.getElementById('transactionStatus');
            statusEl.className = 'status-badge bg-red-100 text-red-800';
            statusEl.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Tempo expirado - Gere nova cobrança
            `;
        }

        // Limpar ao sair da página
        window.addEventListener('beforeunload', () => {
            stopPaymentVerification();
        });

        // Função para regenerar pagamento (botão manual)
        function regeneratePayment() {
            console.log('Regenerando pagamento...');
            generatePayment();
        }

        // Gerar pagamento automaticamente ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Página carregada, gerando QR Code automaticamente...');
            // Aguardar um pouco para garantir que tudo carregou
            setTimeout(() => {
                generatePayment();
            }, 500);
        });

        function copyPixCode() {
            const input = document.getElementById('pixCodeInput');
            const copyBtn = document.getElementById('copyBtn');
            
            if (input.value && input.value !== 'Aguardando geração do código PIX...') {
                input.select();
                input.setSelectionRange(0, 99999);
                
                try {
                    document.execCommand('copy');
                    
                    // Feedback visual
                    const originalHTML = copyBtn.innerHTML;
                    copyBtn.classList.add('copied');
                    copyBtn.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Copiado!</span>
                    `;
                    
                    setTimeout(() => {
                        copyBtn.classList.remove('copied');
                        copyBtn.innerHTML = originalHTML;
                    }, 2000);
                } catch (err) {
                    console.error('Erro ao copiar:', err);
                    alert('Não foi possível copiar. Tente selecionar e copiar manualmente.');
                }
            } else {
                alert('Aguarde a geração do código PIX primeiro.');
            }
        }
    </script>
</body>
</html>
