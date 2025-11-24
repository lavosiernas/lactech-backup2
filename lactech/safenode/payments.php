<?php
/**
 * SafeNode - Gerenciamento de Pagamentos
 */

session_start();

require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/AsaasAPI.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$message = '';
$messageType = '';

// Buscar pagamentos do usuário
$payments = [];
if ($db && $userId) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, u.username, u.email 
            FROM safenode_payments p
            LEFT JOIN safenode_users u ON p.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $payments = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Payments Error: " . $e->getMessage());
    }
}

// Buscar cliente Asaas
$asaasCustomer = null;
if ($db && $userId) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_asaas_customers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $asaasCustomer = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Asaas Customer Error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <style>
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }
        
        .glass-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .grid-pattern {
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 20px 20px;
        }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans h-full overflow-hidden flex">
    
    <!-- Sidebar -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden bg-black">
        <!-- Header -->
        <header class="h-16 border-b border-white/5 bg-black/50 backdrop-blur-xl sticky top-0 z-40 px-6 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="h-8 w-1 bg-gradient-to-b from-blue-500 to-purple-500 rounded-full"></div>
                <div>
                    <h1 class="text-lg font-bold text-white">Pagamentos</h1>
                    <p class="text-xs text-zinc-500">Gerenciar pagamentos e assinaturas</p>
                </div>
            </div>
        </header>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- Criar Novo Pagamento -->
                <div class="glass-card rounded-xl p-6 relative overflow-hidden">
                    <div class="absolute inset-0 grid-pattern"></div>
                    <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-2.5 bg-blue-500/10 rounded-lg text-blue-400 border border-blue-500/20">
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Criar Novo Pagamento</h2>
                                <p class="text-xs text-zinc-500">Gere uma cobrança via Asaas</p>
                            </div>
                        </div>
                        
                        <form id="paymentForm" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-zinc-400 mb-2">Valor (R$)</label>
                                    <input type="number" step="0.01" min="0.01" id="paymentValue" required
                                        class="w-full px-4 py-2.5 rounded-lg bg-zinc-900/50 border border-white/10 text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                        placeholder="0.00">
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-zinc-400 mb-2">Tipo de Pagamento</label>
                                    <select id="billingType" required
                                        class="w-full px-4 py-2.5 rounded-lg bg-zinc-900/50 border border-white/10 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                                        <option value="PIX">PIX</option>
                                        <option value="BOLETO">Boleto</option>
                                        <option value="CREDIT_CARD">Cartão de Crédito</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-zinc-400 mb-2">Data de Vencimento</label>
                                    <input type="date" id="dueDate" required
                                        class="w-full px-4 py-2.5 rounded-lg bg-zinc-900/50 border border-white/10 text-white focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                        min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-zinc-400 mb-2">Descrição</label>
                                <input type="text" id="description"
                                    class="w-full px-4 py-2.5 rounded-lg bg-zinc-900/50 border border-white/10 text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50"
                                    placeholder="Descrição do pagamento">
                            </div>
                            
                            <button type="submit" class="w-full md:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-semibold transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Criar Pagamento
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de Pagamentos -->
                <div class="glass-card rounded-xl overflow-hidden">
                    <div class="p-6 border-b border-white/5 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-emerald-500/10 rounded-lg text-emerald-400 border border-emerald-500/20">
                                <i data-lucide="layout-list" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Histórico de Pagamentos</h2>
                                <p class="text-xs text-zinc-500"><?php echo count($payments); ?> pagamento(s) encontrado(s)</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <?php if (empty($payments)): ?>
                            <div class="p-12 text-center">
                                <i data-lucide="credit-card" class="w-12 h-12 text-zinc-600 mx-auto mb-4"></i>
                                <p class="text-zinc-500">Nenhum pagamento encontrado</p>
                            </div>
                        <?php else: ?>
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-900/50 text-zinc-400 font-medium uppercase text-xs">
                                    <tr>
                                        <th class="px-6 py-4 text-left">ID</th>
                                        <th class="px-6 py-4 text-left">Valor</th>
                                        <th class="px-6 py-4 text-left">Tipo</th>
                                        <th class="px-6 py-4 text-left">Status</th>
                                        <th class="px-6 py-4 text-left">Vencimento</th>
                                        <th class="px-6 py-4 text-left">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-white/5">
                                    <?php foreach ($payments as $payment): ?>
                                        <tr class="hover:bg-white/5 transition-colors">
                                            <td class="px-6 py-4 font-mono text-xs text-zinc-400">
                                                <?php echo htmlspecialchars(substr($payment['asaas_payment_id'] ?? 'N/A', 0, 20)); ?>...
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-white">
                                                R$ <?php echo number_format($payment['amount'], 2, ',', '.'); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-zinc-800 text-zinc-300 border border-white/5">
                                                    <?php echo htmlspecialchars($payment['billing_type'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                $status = $payment['status'] ?? 'PENDING';
                                                $statusColors = [
                                                    'PENDING' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                                    'RECEIVED' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                                    'CONFIRMED' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                                    'OVERDUE' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                                    'REFUNDED' => 'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
                                                ];
                                                $colorClass = $statusColors[$status] ?? 'bg-zinc-800 text-zinc-400 border-white/5';
                                                ?>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium border <?php echo $colorClass; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-zinc-400 text-xs">
                                                <?php echo $payment['due_date'] ? date('d/m/Y', strtotime($payment['due_date'])) : 'N/A'; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-2">
                                                    <?php if ($payment['billing_type'] === 'PIX' && $status === 'PENDING'): ?>
                                                        <button onclick="getPixQrCode('<?php echo htmlspecialchars($payment['asaas_payment_id']); ?>')" 
                                                            class="px-3 py-1.5 text-xs font-medium bg-blue-500/10 text-blue-400 border border-blue-500/20 rounded-lg hover:bg-blue-500/20 transition-all">
                                                            Ver QR Code
                                                        </button>
                                                    <?php endif; ?>
                                                    <a href="https://www.asaas.com/payment/<?php echo htmlspecialchars($payment['asaas_payment_id']); ?>" 
                                                        target="_blank"
                                                        class="px-3 py-1.5 text-xs font-medium bg-zinc-800 text-zinc-300 border border-white/5 rounded-lg hover:bg-zinc-700 transition-all">
                                                        Ver na Asaas
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal PIX QR Code -->
    <div id="pixModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm" x-data="{ open: false }">
        <div class="w-full max-w-md mx-4 rounded-xl bg-zinc-950 border border-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-white">QR Code PIX</h3>
                <button onclick="closePixModal()" class="text-zinc-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div id="pixContent" class="text-center">
                <div class="animate-pulse text-zinc-500">Carregando...</div>
            </div>
        </div>
    </div>
    
    <script>
        lucide.createIcons();
        
        // Formulário de pagamento
        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                value: parseFloat(document.getElementById('paymentValue').value),
                billingType: document.getElementById('billingType').value,
                dueDate: document.getElementById('dueDate').value,
                description: document.getElementById('description').value || 'Pagamento SafeNode'
            };
            
            try {
                const response = await fetch('api/create-payment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Pagamento criado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro: ' + result.error);
                }
            } catch (error) {
                alert('Erro ao criar pagamento: ' + error.message);
            }
        });
        
        // Buscar QR Code PIX
        async function getPixQrCode(paymentId) {
            const modal = document.getElementById('pixModal');
            const content = document.getElementById('pixContent');
            
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            content.innerHTML = '<div class="animate-pulse text-zinc-500">Carregando QR Code...</div>';
            
            try {
                const response = await fetch(`api/get-pix-qrcode.php?payment_id=${paymentId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const pix = result.data;
                    content.innerHTML = `
                        <div class="space-y-4">
                            <div class="bg-white p-4 rounded-lg inline-block">
                                <img src="${pix.encodedImage || ''}" alt="QR Code PIX" class="w-64 h-64">
                            </div>
                            <div class="bg-zinc-900/50 p-4 rounded-lg border border-white/10">
                                <p class="text-xs text-zinc-400 mb-2">Código PIX Copia e Cola:</p>
                                <p class="text-xs font-mono text-white break-all">${pix.payload || ''}</p>
                                <button onclick="copyPixCode('${pix.payload || ''}')" 
                                    class="mt-3 w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                    Copiar Código
                                </button>
                            </div>
                        </div>
                    `;
                    lucide.createIcons();
                } else {
                    content.innerHTML = '<p class="text-red-400">Erro ao buscar QR Code: ' + (result.error || 'Erro desconhecido') + '</p>';
                }
            } catch (error) {
                content.innerHTML = '<p class="text-red-400">Erro: ' + error.message + '</p>';
            }
        }
        
        function closePixModal() {
            const modal = document.getElementById('pixModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        function copyPixCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                alert('Código PIX copiado!');
            });
        }
    </script>
</body>
</html>

