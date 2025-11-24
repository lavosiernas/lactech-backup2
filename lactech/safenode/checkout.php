<?php
/**
 * SafeNode - Checkout de Assinatura
 */

session_start();

require_once __DIR__ . '/includes/SecurityHelpers.php';
SecurityHeaders::apply();

// Verificar se está logado
if (!isset($_SESSION['safenode_logged_in']) || $_SESSION['safenode_logged_in'] !== true) {
    header('Location: login.php?redirect=checkout&plan=' . urlencode($_GET['plan'] ?? ''));
    exit;
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/AsaasAPI.php';

$db = getSafeNodeDatabase();
$userId = $_SESSION['safenode_user_id'] ?? null;
$planKey = $_GET['plan'] ?? 'pro';
$error = '';
$message = '';

// Buscar plano
$plan = null;
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM safenode_plans WHERE plan_key = ? AND is_active = 1");
        $stmt->execute([$planKey]);
        $plan = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Checkout Error: " . $e->getMessage());
    }
}

if (!$plan) {
    header('Location: index.php#pricing');
    exit;
}

// Processar checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe'])) {
    if (!CSRFProtection::validate()) {
        $error = 'Token de segurança inválido. Recarregue a página e tente novamente.';
    } else {
        try {
            $billingType = $_POST['billing_type'] ?? 'PIX';
            $asaasAPI = new AsaasAPI($db);
            
            // Buscar ou criar cliente na Asaas
            $stmt = $db->prepare("SELECT asaas_customer_id FROM safenode_asaas_customers WHERE user_id = ?");
            $stmt->execute([$userId]);
            $customerData = $stmt->fetch();
            
            $asaasCustomerId = null;
            
            if ($customerData && !empty($customerData['asaas_customer_id'])) {
                $asaasCustomerId = $customerData['asaas_customer_id'];
            } else {
                // Buscar dados do usuário
                $stmt = $db->prepare("SELECT username, email, full_name FROM safenode_users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    throw new Exception('Usuário não encontrado');
                }
                
                // Criar cliente na Asaas
                $customerResult = $asaasAPI->createOrUpdateCustomer([
                    'name' => $user['full_name'] ?: $user['username'],
                    'email' => $user['email'],
                    'externalReference' => (string)$userId
                ]);
                
                if (!$customerResult['success']) {
                    throw new Exception('Erro ao criar cliente: ' . $customerResult['error']);
                }
                
                $asaasCustomerId = $customerResult['data']['id'];
                
                // Salvar cliente no banco
                $stmt = $db->prepare("
                    INSERT INTO safenode_asaas_customers (user_id, asaas_customer_id, name, email)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        asaas_customer_id = VALUES(asaas_customer_id),
                        name = VALUES(name),
                        email = VALUES(email),
                        updated_at = NOW()
                ");
                $stmt->execute([
                    $userId,
                    $asaasCustomerId,
                    $user['full_name'] ?: $user['username'],
                    $user['email']
                ]);
            }
            
            // Se o plano for gratuito (Hobby), apenas criar assinatura local
            if ($plan['price'] == 0) {
                // Criar assinatura gratuita
                $stmt = $db->prepare("
                    INSERT INTO safenode_subscriptions (user_id, plan_id, status, billing_cycle, current_period_start, current_period_end)
                    VALUES (?, ?, 'active', 'monthly', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH))
                    ON DUPLICATE KEY UPDATE 
                        plan_id = VALUES(plan_id),
                        status = 'active',
                        updated_at = NOW()
                ");
                $stmt->execute([$userId, $plan['id']]);
                
                $message = 'Plano ativado com sucesso!';
                header('Location: dashboard.php?subscription=active');
                exit;
            }
            
            // Criar assinatura na Asaas
            $subscriptionData = [
                'customer' => $asaasCustomerId,
                'billingType' => $billingType,
                'value' => $plan['price'],
                'cycle' => 'MONTHLY',
                'description' => 'Assinatura SafeNode - ' . $plan['name'],
                'externalReference' => "SUB-$userId-" . time(),
                'nextDueDate' => date('Y-m-d', strtotime('+1 month'))
            ];
            
            // Adicionar dados do cartão se necessário
            if ($billingType === 'CREDIT_CARD' && isset($_POST['credit_card_token'])) {
                $subscriptionData['creditCardToken'] = $_POST['credit_card_token'];
            }
            
            $subscriptionResult = $asaasAPI->createSubscription($subscriptionData);
            
            if (!$subscriptionResult['success']) {
                throw new Exception('Erro ao criar assinatura: ' . $subscriptionResult['error']);
            }
            
            $subscription = $subscriptionResult['data'];
            
            // Salvar assinatura no banco
            $stmt = $db->prepare("
                INSERT INTO safenode_subscriptions (
                    user_id, plan_id, asaas_subscription_id, status, 
                    billing_cycle, current_period_start, current_period_end, metadata
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    plan_id = VALUES(plan_id),
                    asaas_subscription_id = VALUES(asaas_subscription_id),
                    status = VALUES(status),
                    updated_at = NOW()
            ");
            
            $periodStart = date('Y-m-d');
            $periodEnd = date('Y-m-d', strtotime('+1 month'));
            $metadata = json_encode($subscription);
            
            $stmt->execute([
                $userId,
                $plan['id'],
                $subscription['id'],
                'pending',
                'monthly',
                $periodStart,
                $periodEnd,
                $metadata
            ]);
            
            // Se for PIX, redirecionar para página de pagamento
            if ($billingType === 'PIX') {
                header('Location: subscription-payment.php?subscription_id=' . $subscription['id']);
                exit;
            }
            
            $message = 'Assinatura criada com sucesso!';
            header('Location: dashboard.php?subscription=active');
            exit;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo htmlspecialchars($plan['name']); ?> | SafeNode</title>
    <link rel="icon" type="image/png" href="assets/img/logos (6).png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
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
        
        .glass-card {
            background: linear-gradient(180deg, rgba(39, 39, 42, 0.4) 0%, rgba(24, 24, 27, 0.4) 100%);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-black text-zinc-200 font-sans min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-4xl">
        <div class="mb-8 text-center">
            <a href="index.php" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white mb-4">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Voltar
            </a>
            <h1 class="text-3xl font-bold text-white mb-2">Finalizar Assinatura</h1>
            <p class="text-zinc-500">Plano: <?php echo htmlspecialchars($plan['name']); ?></p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Resumo do Plano -->
            <div class="md:col-span-2 glass-card rounded-xl p-6">
                <?php if ($error): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-500/10 border border-red-500/30 text-red-200 flex items-start gap-3">
                        <i data-lucide="alert-circle" class="w-5 h-5 shrink-0 mt-0.5"></i>
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="subscribe" value="1">
                    
                    <div>
                        <label class="block text-sm font-medium text-zinc-300 mb-3">Forma de Pagamento</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative">
                                <input type="radio" name="billing_type" value="PIX" checked class="peer hidden">
                                <div class="p-4 rounded-lg border-2 border-zinc-800 peer-checked:border-blue-500 bg-zinc-900/50 cursor-pointer hover:bg-zinc-900 transition-all">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="smartphone" class="w-5 h-5 text-blue-400"></i>
                                        <span class="font-medium text-white">PIX</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative">
                                <input type="radio" name="billing_type" value="CREDIT_CARD" class="peer hidden">
                                <div class="p-4 rounded-lg border-2 border-zinc-800 peer-checked:border-blue-500 bg-zinc-900/50 cursor-pointer hover:bg-zinc-900 transition-all">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="credit-card" class="w-5 h-5 text-blue-400"></i>
                                        <span class="font-medium text-white">Cartão</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-bold text-lg transition-all shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Assinar <?php echo htmlspecialchars($plan['name']); ?>
                    </button>
                </form>
            </div>
            
            <!-- Resumo -->
            <div class="glass-card rounded-xl p-6">
                <h3 class="text-lg font-bold text-white mb-4">Resumo</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-400">Plano</span>
                        <span class="font-semibold text-white"><?php echo htmlspecialchars($plan['name']); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-400">Valor</span>
                        <span class="text-2xl font-bold text-white">
                            R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-zinc-400">Ciclo</span>
                        <span class="font-semibold text-white">Mensal</span>
                    </div>
                    <div class="pt-4 border-t border-white/10">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-white">Total</span>
                            <span class="text-2xl font-bold text-white">
                                R$ <?php echo number_format($plan['price'], 2, ',', '.'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6 p-4 rounded-lg bg-zinc-900/50 border border-white/10">
                    <h4 class="text-sm font-semibold text-white mb-2">Recursos incluídos:</h4>
                    <ul class="space-y-2 text-sm text-zinc-400">
                        <?php 
                        $features = json_decode($plan['features'] ?? '[]', true);
                        foreach ($features as $feature): 
                        ?>
                            <li class="flex items-center gap-2">
                                <i data-lucide="check" class="w-4 h-4 text-emerald-400"></i>
                                <?php echo htmlspecialchars($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        lucide.createIcons();
    </script>
</body>
</html>

