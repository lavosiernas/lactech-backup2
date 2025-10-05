<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/rbac.php';

$auth = new Auth();
$rbac = RBAC::getInstance();

// Verificar autenticação
$auth->requireLogin();
$auth->require2FA();

$user = $auth->getCurrentUser();

// Verificar permissões
$allowedRoles = ['gerente', 'funcionario', 'veterinario', 'proprietario'];
if (!in_array($user['role'], $allowedRoles)) {
    setNotification('Você não tem permissão para acessar relatórios', 'error');
    redirect(DASHBOARD_URL);
}

$error = '';
$success = '';

// Verificar notificação
$notification = getNotification();
if ($notification) {
    if ($notification['type'] === 'success') {
        $success = $notification['message'];
    } else {
        $error = $notification['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - LacTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="https://i.postimg.cc/vmrkgDcB/lactech.png" type="image/x-icon">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .card {
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <img src="https://i.postimg.cc/vmrkgDcB/lactech.png" alt="LacTech" class="h-8">
                    <h1 class="text-xl font-semibold text-gray-900">Relatórios - LacTech</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                    
                    <a href="../dashboard.php" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Mensagens -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                Gerador de Relatórios
            </h2>
            <p class="text-gray-600">
                Selecione o tipo de relatório e configure os parâmetros desejados
            </p>
        </div>

        <!-- Report Types Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Relatório de Volume -->
            <div class="card bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Relatório de Volume</h3>
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm mb-6">
                    Relatório detalhado de produção de leite com volume total, médias e registros por período.
                </p>
                
                <button onclick="openReportModal('volume')" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition duration-200">
                    Gerar Relatório
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Relatório de Qualidade -->
            <div class="card bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Relatório de Qualidade</h3>
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm mb-6">
                    Relatório de testes de qualidade do leite com gordura, proteína, CCS e CBT.
                </p>
                
                <button onclick="openReportModal('quality')" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition duration-200">
                    Gerar Relatório
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Relatório de Pagamentos -->
            <?php if (in_array($user['role'], ['gerente', 'proprietario'])): ?>
            <div class="card bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Relatório de Pagamentos</h3>
                    <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <p class="text-gray-600 text-sm mb-6">
                    Relatório financeiro com receitas, pagamentos e análise de faturamento.
                </p>
                
                <button onclick="openReportModal('payments')" 
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 transition duration-200">
                    Gerar Relatório
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal para configuração de relatórios -->
    <div id="reportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="modalTitle" class="text-lg font-medium text-gray-900 mb-4"></h3>
                
                <form id="reportForm" method="GET">
                    <div class="mb-4">
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Data Inicial
                        </label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               value="<?php echo date('Y-m-01'); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Data Final
                        </label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" 
                                   id="preview" 
                                   name="preview" 
                                   value="1"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700">Gerar como prévia (com marca d'água)</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="closeReportModal()"
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition duration-200">
                            Cancelar
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                            Gerar PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openReportModal(reportType) {
            const modal = document.getElementById('reportModal');
            const form = document.getElementById('reportForm');
            const title = document.getElementById('modalTitle');
            
            // Configurar título e ação do formulário
            const titles = {
                'volume': 'Relatório de Volume',
                'quality': 'Relatório de Qualidade',
                'payments': 'Relatório de Pagamentos'
            };
            
            title.textContent = titles[reportType];
            form.action = reportType + '.php';
            
            modal.classList.remove('hidden');
        }
        
        function closeReportModal() {
            document.getElementById('reportModal').classList.add('hidden');
        }
        
        // Fechar modal ao clicar fora dele
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReportModal();
            }
        });
    </script>
</body>
</html>




