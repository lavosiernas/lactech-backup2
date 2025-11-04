<?php
/**
 * Administração de Pagamentos PIX - LacPay
 * Página para administradores verificarem e confirmarem pagamentos
 */

require_once __DIR__ . '/includes/config_login.php';

// Verificar autenticação (apenas gerentes/proprietários)
if (!isLoggedIn()) {
    header('Location: inicio-login.php');
    exit;
}

$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['gerente', 'manager', 'proprietario', 'owner'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/Database.class.php';

$db = Database::getInstance();
$error = '';
$success = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'confirm_payment') {
            $txid = $_POST['txid'] ?? '';
            if ($txid) {
                try {
                    $userId = $_SESSION['user_id'];
                    $db->query("
                        UPDATE pix_payments 
                        SET status = 'pago',
                            paid_at = NOW(),
                            verified_by = ?,
                            verified_at = NOW(),
                            updated_at = NOW()
                        WHERE txid = ?
                    ", [$userId, $txid]);
                    $success = 'Pagamento confirmado com sucesso!';
                } catch (Exception $e) {
                    $error = 'Erro ao confirmar pagamento: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'cancel_payment') {
            $txid = $_POST['txid'] ?? '';
            if ($txid) {
                try {
                    $db->query("
                        UPDATE pix_payments 
                        SET status = 'cancelado',
                            updated_at = NOW()
                        WHERE txid = ?
                    ", [$txid]);
                    $success = 'Pagamento cancelado com sucesso!';
                } catch (Exception $e) {
                    $error = 'Erro ao cancelar pagamento: ' . $e->getMessage();
                }
            }
        }
    }
}

// Buscar pagamentos
$statusFilter = $_GET['status'] ?? 'pendente';
try {
    if ($statusFilter === 'todos') {
        $payments = $db->query("
            SELECT 
                p.*,
                u.name as verified_by_name
            FROM pix_payments p
            LEFT JOIN users u ON p.verified_by = u.id
            ORDER BY p.created_at DESC
            LIMIT 100
        ");
    } else {
        $payments = $db->query("
            SELECT 
                p.*,
                u.name as verified_by_name
            FROM pix_payments p
            LEFT JOIN users u ON p.verified_by = u.id
            WHERE p.status = ?
            ORDER BY p.created_at DESC
            LIMIT 100
        ", [$statusFilter]);
    }
} catch (Exception $e) {
    $payments = [];
    $error = 'Erro ao carregar pagamentos: ' . $e->getMessage();
}

// Estatísticas
try {
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes,
            SUM(CASE WHEN status = 'pago' THEN 1 ELSE 0 END) as pagos,
            SUM(CASE WHEN status = 'pago' THEN plan_value ELSE 0 END) as total_pago
        FROM pix_payments
    ");
    $stats = $stats[0] ?? ['total' => 0, 'pendentes' => 0, 'pagos' => 0, 'total_pago' => 0];
} catch (Exception $e) {
    $stats = ['total' => 0, 'pendentes' => 0, 'pagos' => 0, 'total_pago' => 0];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Pagamentos PIX | LacTech</title>
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="gerente-completo.php" class="text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Administração - Pagamentos PIX</h1>
                </div>
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>LacPay</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="text-sm text-gray-600 mb-1">Total de Pagamentos</div>
                <div class="text-3xl font-bold text-gray-900"><?php echo $stats['total']; ?></div>
            </div>
            <div class="bg-yellow-50 rounded-xl p-6 shadow-sm border border-yellow-200">
                <div class="text-sm text-yellow-700 mb-1">Pendentes</div>
                <div class="text-3xl font-bold text-yellow-800"><?php echo $stats['pendentes']; ?></div>
            </div>
            <div class="bg-green-50 rounded-xl p-6 shadow-sm border border-green-200">
                <div class="text-sm text-green-700 mb-1">Confirmados</div>
                <div class="text-3xl font-bold text-green-800"><?php echo $stats['pagos']; ?></div>
            </div>
            <div class="bg-blue-50 rounded-xl p-6 shadow-sm border border-blue-200">
                <div class="text-sm text-blue-700 mb-1">Total Recebido</div>
                <div class="text-3xl font-bold text-blue-800">R$ <?php echo number_format($stats['total_pago'], 2, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl p-4 mb-6 shadow-sm">
            <div class="flex items-center space-x-4">
                <span class="text-sm font-medium text-gray-700">Filtrar por:</span>
                <a href="?status=pendente" class="px-4 py-2 rounded-lg <?php echo $statusFilter === 'pendente' ? 'bg-yellow-100 text-yellow-800 font-semibold' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    Pendentes
                </a>
                <a href="?status=pago" class="px-4 py-2 rounded-lg <?php echo $statusFilter === 'pago' ? 'bg-green-100 text-green-800 font-semibold' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    Pagos
                </a>
                <a href="?status=todos" class="px-4 py-2 rounded-lg <?php echo $statusFilter === 'todos' ? 'bg-gray-100 text-gray-800 font-semibold' : 'text-gray-600 hover:bg-gray-100'; ?>">
                    Todos
                </a>
            </div>
        </div>

        <!-- Tabela de Pagamentos -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TXID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plano</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Criado em</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Verificado por</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    Nenhum pagamento encontrado
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $payment): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <code class="text-xs text-gray-700"><?php echo htmlspecialchars(substr($payment['txid'], 0, 20)) . '...'; ?></code>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($payment['plan_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                        R$ <?php echo number_format($payment['plan_value'], 2, ',', '.'); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusColors = [
                                            'pendente' => 'bg-yellow-100 text-yellow-800',
                                            'pago' => 'bg-green-100 text-green-800',
                                            'expirado' => 'bg-red-100 text-red-800',
                                            'cancelado' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $color = $statusColors[$payment['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $color; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <?php echo $payment['verified_by_name'] ? htmlspecialchars($payment['verified_by_name']) : '-'; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($payment['status'] === 'pendente'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('Confirma que este pagamento foi realizado?')">
                                                <input type="hidden" name="txid" value="<?php echo htmlspecialchars($payment['txid']); ?>">
                                                <input type="hidden" name="action" value="confirm_payment">
                                                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-semibold">
                                                    Confirmar Pagamento
                                                </button>
                                            </form>
                                            <form method="POST" class="inline ml-2" onsubmit="return confirm('Tem certeza que deseja cancelar este pagamento?')">
                                                <input type="hidden" name="txid" value="<?php echo htmlspecialchars($payment['txid']); ?>">
                                                <input type="hidden" name="action" value="cancel_payment">
                                                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-semibold">
                                                    Cancelar
                                                </button>
                                            </form>
                                        <?php elseif ($payment['status'] === 'pago'): ?>
                                            <span class="text-sm text-green-600 font-semibold">✓ Confirmado</span>
                                            <?php if ($payment['paid_at']): ?>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?php echo date('d/m/Y H:i', strtotime($payment['paid_at'])); ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Instruções -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Como verificar pagamentos:</h3>
            <ol class="text-sm text-gray-700 space-y-2 list-decimal list-inside">
                <li>Acesse o extrato bancário da conta PIX (slavosier298@gmail.com)</li>
                <li>Procure por transações que correspondam ao valor e TXID do pagamento</li>
                <li>Confirme que a transação foi realmente recebida</li>
                <li>Clique em "Confirmar Pagamento" quando tiver certeza</li>
                <li>O sistema enviará notificação automática ao cliente quando o pagamento for confirmado</li>
            </ol>
        </div>
    </main>
</body>
</html>

