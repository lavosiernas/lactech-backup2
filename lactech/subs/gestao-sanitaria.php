<?php
/**
 * Página: Gestão Sanitária
 * Subpágina do Mais Opções
 */

require_once __DIR__ . '/../includes/config_login.php';
require_once __DIR__ . '/../includes/Database.class.php';

if (!isLoggedIn() || ($_SESSION['user_role'] !== 'gerente' && $_SESSION['user_role'] !== 'manager')) {
    http_response_code(403);
    die('Acesso negado');
}

$v = time();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Sanitária - LacTech</title>
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 bg-white sticky top-0 z-10 shadow-sm border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Gestão Sanitária</h2>
                    <p class="text-sm text-gray-600">Controle de saúde e bem-estar do rebanho</p>
                </div>
            </div>
            <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="space-y-4 px-2 p-6">
            <!-- Indicadores Sanitários -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-4">
                <h3 class="text-base font-bold text-gray-900 mb-3">Indicadores Sanitários</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-green-600">2.1%</p>
                        <p class="text-xs text-gray-600">Mortalidade</p>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-blue-600">5</p>
                        <p class="text-xs text-gray-600">Casos Mastite</p>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-purple-600">95%</p>
                        <p class="text-xs text-gray-600">Vacinação OK</p>
                    </div>
                    <div class="text-center p-3 bg-white rounded-lg shadow-sm">
                        <p class="text-xl font-bold text-orange-600">3</p>
                        <p class="text-xs text-gray-600">Doentes</p>
                    </div>
                </div>
            </div>

            <!-- Ações Essenciais -->
            <div class="grid grid-cols-1 gap-3">
                <button onclick="window.parent.postMessage({type: 'openModal', page: 'registrar-doenca'}, '*')" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-green-50 hover:border-green-300 transition-all">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="flex-1 text-left">
                        <p class="font-medium text-gray-900">Registrar Doença</p>
                        <p class="text-sm text-gray-600">Diagnóstico e Tratamento</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                
                <button onclick="window.parent.postMessage({type: 'openModal', page: 'aplicar-vacina'}, '*')" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition-all">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="flex-1 text-left">
                        <p class="font-medium text-gray-900">Aplicar Vacina</p>
                        <p class="text-sm text-gray-600">Controle de Vacinação</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                
                <button onclick="window.parent.postMessage({type: 'openModal', page: 'controle-mastite'}, '*')" class="flex items-center p-4 bg-white rounded-xl border border-gray-200 hover:bg-pink-50 hover:border-pink-300 transition-all">
                    <div class="w-12 h-12 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
                        <svg class="w-6 h-6 text-pink-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="flex-1 text-left">
                        <p class="font-medium text-gray-900">Controle Mastite</p>
                        <p class="text-sm text-gray-600">Teste e Tratamento</p>
                    </div>
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>

            <!-- Alertas Sanitários -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h3 class="text-base font-bold text-gray-900 mb-3">Alertas Sanitários</h3>
                <div class="space-y-3">
                    <div id="vaccination-alerts-container" class="space-y-3">
                        <!-- Alertas carregados via JavaScript -->
                    </div>
                    <div id="mastitis-alerts-container" class="space-y-3">
                        <!-- Alertas carregados via JavaScript -->
                    </div>
                    <div id="medicine-alerts-container" class="space-y-3">
                        <!-- Alertas carregados via JavaScript -->
                    </div>
                </div>
            </div>

            <!-- Controle de Vacinação -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h3 class="text-base font-bold text-gray-900 mb-3">Próximas Vacinações</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Aftosa (Maio)</p>
                            <p class="text-xs text-gray-600">230 animais</p>
                        </div>
                        <span class="text-lg font-bold text-blue-600">15 dias</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-700">Brucelose</p>
                            <p class="text-xs text-gray-600">45 novilhas</p>
                        </div>
                        <span class="text-lg font-bold text-green-600">30 dias</span>
                    </div>
                </div>
            </div>

            <!-- Biossegurança -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                <h3 class="text-base font-bold text-gray-900 mb-3">Biossegurança</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 text-sm">Quarentena Ativa</p>
                            <p class="text-xs text-gray-600">2 animais novos</p>
                        </div>
                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">7 dias</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 text-sm">Limpeza Ordenha</p>
                            <p class="text-xs text-gray-600">Última: hoje 14:00</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">OK</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900 text-sm">Controle Acesso</p>
                            <p class="text-xs text-gray-600">Visitas registradas</p>
                        </div>
                        <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Ativo</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

