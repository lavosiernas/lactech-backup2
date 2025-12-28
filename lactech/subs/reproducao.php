<?php
/**
 * Página: Gestão Reprodutiva
 * Subpágina do Mais Opções - Sistema completo de gestão do ciclo reprodutivo
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
    <title>Gestão Reprodutiva - LacTech</title>
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    <script src="../assets/js/toast-notifications.js?v=<?php echo $v; ?>"></script>
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Toast Container -->
    <div id="toastContainer" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <div class="flex items-center justify-between p-6 bg-white sticky top-0 z-10 shadow-sm border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-gradient-to-br from-pink-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Gestão Reprodutiva</h2>
                    <p class="text-sm text-gray-500">Controle completo do ciclo reprodutivo</p>
                </div>
            </div>
            <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-xl transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <!-- Dashboard -->
            <div class="mb-6 bg-gradient-to-r from-pink-50 to-purple-50 rounded-xl p-6 border border-pink-200 fade-in">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Indicadores Reprodutivos</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-pink-600" id="stat-pregnancies">0</p>
                        <p class="text-sm text-gray-600 mt-1">Prenhezes</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-green-600" id="stat-conception">0%</p>
                        <p class="text-sm text-gray-600 mt-1">Taxa de Concepção</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-blue-600" id="stat-iep">0</p>
                        <p class="text-sm text-gray-600 mt-1">IEP (dias)</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-orange-600" id="stat-first-calving">0</p>
                        <p class="text-sm text-gray-600 mt-1">1º Parto (meses)</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-2xl font-bold text-yellow-600" id="stat-pending-tests">0</p>
                        <p class="text-xs text-gray-600 mt-1">Testes Pendentes</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-2xl font-bold text-red-600" id="stat-expected-calvings">0</p>
                        <p class="text-xs text-gray-600 mt-1">Partos (30 dias)</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-2xl font-bold text-purple-600" id="stat-expected-heats">0</p>
                        <p class="text-xs text-gray-600 mt-1">Cios (7 dias)</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button onclick="switchTab('inseminations')" class="tab-button active px-6 py-4 text-sm font-medium text-pink-600 border-b-2 border-pink-600" data-tab="inseminations">
                            Inseminações
                        </button>
                        <button onclick="switchTab('pregnancies')" class="tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent" data-tab="pregnancies">
                            Controles de Prenhez
                        </button>
                        <button onclick="switchTab('heats')" class="tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent" data-tab="heats">
                            Cios
                        </button>
                        <button onclick="switchTab('births')" class="tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 border-b-2 border-transparent" data-tab="births">
                            Partos
                        </button>
                    </nav>
                </div>

                <!-- Tab: Inseminações -->
                <div id="tab-inseminations" class="tab-content active p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Inseminações</h3>
                        <button onclick="openInseminationForm()" class="px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-medium">
                            + Nova Inseminação
                        </button>
                    </div>
                    <div id="inseminations-list" class="space-y-3">
                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <p>Carregando inseminações...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab: Controles de Prenhez -->
                <div id="tab-pregnancies" class="tab-content p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Controles de Prenhez</h3>
                        <button onclick="openPregnancyForm()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                            + Novo Controle
                        </button>
                    </div>
                    <div id="pregnancies-list" class="space-y-3">
                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <p>Carregando controles...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab: Cios -->
                <div id="tab-heats" class="tab-content p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Cios</h3>
                        <button onclick="openHeatForm()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                            + Registrar Cio
                        </button>
                    </div>
                    <div id="heats-list" class="space-y-3">
                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <p>Carregando cios...</p>
                        </div>
                    </div>
                </div>

                <!-- Tab: Partos -->
                <div id="tab-births" class="tab-content p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">Partos</h3>
                        <button onclick="openBirthForm()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                            + Registrar Parto
                        </button>
                    </div>
                    <div id="births-list" class="space-y-3">
                        <div class="text-center text-gray-500 py-8">
                            <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <p>Carregando partos...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Formulário de Inseminação -->
    <div id="insemination-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900" id="insemination-modal-title">Nova Inseminação</h3>
                <button onclick="closeInseminationForm()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="insemination-form" class="p-6 space-y-4">
                <input type="hidden" id="insemination-id" name="id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Animal *</label>
                        <select id="insemination-animal" name="animal_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Touro</label>
                        <select id="insemination-bull" name="bull_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data da Inseminação *</label>
                        <input type="date" id="insemination-date" name="insemination_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                        <input type="time" id="insemination-time" name="insemination_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select id="insemination-type" name="insemination_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="inseminacao_artificial">Inseminação Artificial</option>
                            <option value="natural">Monta Natural</option>
                            <option value="transferencia_embriao">Transferência de Embrião</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Método</label>
                        <select id="insemination-method" name="insemination_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                            <option value="IA">IA</option>
                            <option value="MO">MO</option>
                            <option value="FIV">FIV</option>
                            <option value="IATF">IATF</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Técnico</label>
                        <input type="text" id="insemination-technician" name="technician_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lote de Sêmen</label>
                        <input type="text" id="insemination-batch" name="semen_batch" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Palheta</label>
                        <input type="text" id="insemination-straw" name="semen_straw_number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custo (R$)</label>
                        <input type="number" step="0.01" id="insemination-cost" name="cost" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="insemination-notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pink-500 focus:border-pink-500"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg hover:bg-pink-700 transition-colors font-medium">
                        Salvar
                    </button>
                    <button type="button" onclick="closeInseminationForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Formulário de Controle de Prenhez -->
    <div id="pregnancy-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Novo Controle de Prenhez</h3>
                <button onclick="closePregnancyForm()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="pregnancy-form" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Animal *</label>
                        <select id="pregnancy-animal" name="animal_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data da Prenhez *</label>
                        <input type="date" id="pregnancy-date" name="pregnancy_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Estágio</label>
                        <select id="pregnancy-stage" name="pregnancy_stage" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="inicial">Inicial</option>
                            <option value="meio">Meio</option>
                            <option value="final">Final</option>
                            <option value="pre-parto">Pré-parto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Ultrassom</label>
                        <input type="date" id="pregnancy-ultrasound-date" name="ultrasound_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Resultado do Ultrassom</label>
                        <select id="pregnancy-ultrasound-result" name="ultrasound_result" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Selecione...</option>
                            <option value="positivo">Positivo</option>
                            <option value="negativo">Negativo</option>
                            <option value="indefinido">Indefinido</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="pregnancy-notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        Salvar
                    </button>
                    <button type="button" onclick="closePregnancyForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Formulário de Cio -->
    <div id="heat-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Registrar Cio</h3>
                <button onclick="closeHeatForm()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="heat-form" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Animal *</label>
                        <select id="heat-animal" name="animal_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Cio *</label>
                        <input type="date" id="heat-date" name="heat_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Intensidade</label>
                        <select id="heat-intensity" name="heat_intensity" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <option value="leve">Leve</option>
                            <option value="moderado" selected>Moderado</option>
                            <option value="forte">Forte</option>
                        </select>
                    </div>
                    <div class="flex items-center pt-6">
                        <input type="checkbox" id="heat-planned" name="insemination_planned" class="w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <label for="heat-planned" class="ml-2 text-sm text-gray-700">Inseminação planejada</label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="heat-notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-medium">
                        Salvar
                    </button>
                    <button type="button" onclick="closeHeatForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Formulário de Parto -->
    <div id="birth-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900">Registrar Parto</h3>
                <button onclick="closeBirthForm()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="birth-form" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Animal *</label>
                        <select id="birth-animal" name="animal_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Parto *</label>
                        <input type="date" id="birth-date" name="birth_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora</label>
                        <input type="time" id="birth-time" name="birth_time" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sexo do Bezerro</label>
                        <select id="birth-calf-gender" name="calf_gender" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione...</option>
                            <option value="macho">Macho</option>
                            <option value="femea">Fêmea</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Peso do Bezerro (kg)</label>
                        <input type="number" step="0.1" id="birth-calf-weight" name="calf_weight" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status do Bezerro</label>
                        <select id="birth-calf-status" name="calf_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="vivo">Vivo</option>
                            <option value="morto">Morto</option>
                            <option value="natimorto">Natimorto</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="birth-notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Salvar
                    </button>
                    <button type="button" onclick="closeBirthForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../api/reproduction.php';
        let currentTab = 'inseminations';
        let animalsList = [];
        let bullsList = [];

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            loadAnimals();
            loadBulls();
            loadInseminations();
        });

        // Tabs
        function switchTab(tab) {
            currentTab = tab;
            
            // Atualizar botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active', 'text-pink-600', 'border-pink-600');
                btn.classList.add('text-gray-500', 'border-transparent');
            });
            event.target.classList.add('active', 'text-pink-600', 'border-pink-600');
            event.target.classList.remove('text-gray-500', 'border-transparent');
            
            // Atualizar conteúdo
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(`tab-${tab}`).classList.add('active');
            
            // Carregar dados da aba
            switch(tab) {
                case 'inseminations':
                    loadInseminations();
                    break;
                case 'pregnancies':
                    loadPregnancies();
                    break;
                case 'heats':
                    loadHeats();
                    break;
                case 'births':
                    loadBirths();
                    break;
            }
        }

        // Dashboard
        async function loadDashboard() {
            try {
                const response = await fetch(`${API_BASE}?action=dashboard`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const stats = result.data;
                    document.getElementById('stat-pregnancies').textContent = stats.pregnancies || 0;
                    document.getElementById('stat-conception').textContent = (stats.conception_rate || 0) + '%';
                    document.getElementById('stat-iep').textContent = stats.avg_iep || 0;
                    document.getElementById('stat-first-calving').textContent = stats.avg_first_calving || 0;
                    document.getElementById('stat-pending-tests').textContent = stats.pending_tests || 0;
                    document.getElementById('stat-expected-calvings').textContent = stats.expected_calvings_30d || 0;
                    document.getElementById('stat-expected-heats').textContent = stats.expected_heats_7d || 0;
                }
            } catch (error) {
                console.error('Erro ao carregar dashboard:', error);
            }
        }

        // Carregar animais e touros
        async function loadAnimals() {
            try {
                const response = await fetch(`${API_BASE}?action=animals`);
                const result = await response.json();
                if (result.success) {
                    animalsList = result.data || [];
                    populateAnimalSelects();
                }
            } catch (error) {
                console.error('Erro ao carregar animais:', error);
            }
        }

        async function loadBulls() {
            try {
                const response = await fetch(`${API_BASE}?action=bulls`);
                const result = await response.json();
                if (result.success) {
                    bullsList = result.data || [];
                    const select = document.getElementById('insemination-bull');
                    if (select) {
                        select.innerHTML = '<option value="">Selecione...</option>' + 
                            bullsList.map(b => `<option value="${b.id}">${b.name || b.bull_number} - ${b.breed || ''}</option>`).join('');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar touros:', error);
            }
        }

        function populateAnimalSelects() {
            const selects = ['insemination-animal', 'pregnancy-animal', 'heat-animal', 'birth-animal'];
            selects.forEach(selectId => {
                const select = document.getElementById(selectId);
                if (select) {
                    select.innerHTML = '<option value="">Selecione...</option>' + 
                        animalsList.map(a => `<option value="${a.id}">${a.animal_number || 'N/A'} - ${a.name || 'Sem nome'}</option>`).join('');
                }
            });
        }

        // Inseminações
        async function loadInseminations() {
            const container = document.getElementById('inseminations-list');
            container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const response = await fetch(`${API_BASE}?action=inseminations_list&limit=50`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const inseminations = result.data.inseminations || [];
                    
                    if (inseminations.length > 0) {
                        container.innerHTML = inseminations.map(i => `
                            <div class="p-4 bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow fade-in">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <p class="font-medium text-gray-900">${i.animal_number || 'N/A'} - ${i.animal_name || 'Sem nome'}</p>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full ${getPregnancyStatusColor(i.pregnancy_result)}">${getPregnancyStatusText(i.pregnancy_result)}</span>
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Data:</strong> ${formatDate(i.insemination_date)}</p>
                                            ${i.bull_name ? `<p><strong>Touro:</strong> ${i.bull_name}</p>` : ''}
                                            ${i.technician_name ? `<p><strong>Técnico:</strong> ${i.technician_name}</p>` : ''}
                                            ${i.expected_calving_date ? `<p><strong>Parto previsto:</strong> ${formatDate(i.expected_calving_date)}</p>` : ''}
                                            ${i.days_since !== undefined ? `<p><strong>Dias desde IA:</strong> ${i.days_since}</p>` : ''}
                                        </div>
                                    </div>
                                    <div class="flex gap-2 ml-4">
                                        ${i.pregnancy_result === 'pendente' && i.days_since >= 21 ? `
                                            <button onclick="openPregnancyForm(${i.id})" class="px-3 py-1 bg-green-600 text-white text-xs rounded-lg hover:bg-green-700">
                                                Teste Prenhez
                                            </button>
                                        ` : ''}
                                        <button onclick="editInsemination(${i.id})" class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                                            Editar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhuma inseminação registrada</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar inseminações:', error);
                container.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar inseminações</div>';
            }
        }

        function openInseminationForm(id = null) {
            document.getElementById('insemination-modal').classList.remove('hidden');
            document.getElementById('insemination-form').reset();
            document.getElementById('insemination-id').value = id || '';
            document.getElementById('insemination-modal-title').textContent = id ? 'Editar Inseminação' : 'Nova Inseminação';
            
            if (id) {
                loadInseminationForEdit(id);
            }
        }

        async function loadInseminationForEdit(id) {
            try {
                const response = await fetch(`${API_BASE}?action=insemination_get&id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const i = result.data;
                    document.getElementById('insemination-animal').value = i.animal_id;
                    document.getElementById('insemination-bull').value = i.bull_id || '';
                    document.getElementById('insemination-date').value = i.insemination_date;
                    document.getElementById('insemination-time').value = i.insemination_time || '';
                    document.getElementById('insemination-type').value = i.insemination_type || 'inseminacao_artificial';
                    document.getElementById('insemination-method').value = i.insemination_method || 'IA';
                    document.getElementById('insemination-technician').value = i.technician_name || '';
                    document.getElementById('insemination-batch').value = i.semen_batch || '';
                    document.getElementById('insemination-straw').value = i.semen_straw_number || '';
                    document.getElementById('insemination-cost').value = i.cost || '';
                    document.getElementById('insemination-notes').value = i.notes || '';
                }
            } catch (error) {
                console.error('Erro ao carregar inseminação:', error);
            }
        }

        function closeInseminationForm() {
            document.getElementById('insemination-modal').classList.add('hidden');
        }

        document.getElementById('insemination-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            try {
                const url = id ? 
                    `${API_BASE}?action=insemination_update` : 
                    `${API_BASE}?action=insemination_create`;
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast(result.data?.message || 'Inseminação salva com sucesso!');
                    }
                    closeInseminationForm();
                    loadInseminations();
                    loadDashboard();
                } else {
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast(result.error || 'Erro ao salvar inseminação');
                    }
                }
            } catch (error) {
                console.error('Erro ao salvar inseminação:', error);
                if (typeof window.showErrorToast === 'function') {
                    window.showErrorToast('Erro ao salvar inseminação');
                }
            }
        });

        function editInsemination(id) {
            openInseminationForm(id);
        }

        // Controles de Prenhez
        async function loadPregnancies() {
            const container = document.getElementById('pregnancies-list');
            container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const response = await fetch(`${API_BASE}?action=pregnancies_list&limit=50`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const pregnancies = result.data.pregnancies || [];
                    
                    if (pregnancies.length > 0) {
                        container.innerHTML = pregnancies.map(p => `
                            <div class="p-4 bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow fade-in">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <p class="font-medium text-gray-900">${p.animal_number || 'N/A'} - ${p.animal_name || 'Sem nome'}</p>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full ${getStageColor(p.pregnancy_stage)}">${p.pregnancy_stage || 'inicial'}</span>
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Data da prenhez:</strong> ${formatDate(p.pregnancy_date)}</p>
                                            <p><strong>Parto previsto:</strong> ${formatDate(p.expected_birth)}</p>
                                            ${p.days_until_birth !== undefined ? `<p><strong>Dias até o parto:</strong> ${p.days_until_birth}</p>` : ''}
                                            ${p.ultrasound_result ? `<p><strong>Ultrassom:</strong> ${p.ultrasound_result}</p>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum controle de prenhez registrado</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar controles:', error);
                container.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar controles</div>';
            }
        }

        function openPregnancyForm(inseminationId = null) {
            document.getElementById('pregnancy-modal').classList.remove('hidden');
            document.getElementById('pregnancy-form').reset();
            
            if (inseminationId) {
                // Preencher com dados da inseminação
                document.getElementById('pregnancy-animal').value = '';
                // TODO: Buscar dados da inseminação
            }
        }

        function closePregnancyForm() {
            document.getElementById('pregnancy-modal').classList.add('hidden');
        }

        document.getElementById('pregnancy-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch(`${API_BASE}?action=pregnancy_create`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Controle de prenhez registrado com sucesso!');
                    }
                    closePregnancyForm();
                    loadPregnancies();
                    loadDashboard();
                } else {
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast(result.error || 'Erro ao salvar controle');
                    }
                }
            } catch (error) {
                console.error('Erro ao salvar controle:', error);
                if (typeof window.showErrorToast === 'function') {
                    window.showErrorToast('Erro ao salvar controle');
                }
            }
        });

        // Cios
        async function loadHeats() {
            const container = document.getElementById('heats-list');
            container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const response = await fetch(`${API_BASE}?action=heats_list&limit=50`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const heats = result.data.heats || [];
                    
                    if (heats.length > 0) {
                        container.innerHTML = heats.map(h => `
                            <div class="p-4 bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow fade-in">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <p class="font-medium text-gray-900">${h.animal_number || 'N/A'} - ${h.animal_name || 'Sem nome'}</p>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full ${getIntensityColor(h.heat_intensity)}">${h.heat_intensity || 'moderado'}</span>
                                            ${h.insemination_planned ? '<span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">IA Planejada</span>' : ''}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <p><strong>Data:</strong> ${formatDate(h.heat_date)}</p>
                                            ${h.days_ago !== undefined ? `<p><strong>Há:</strong> ${h.days_ago} dias</p>` : ''}
                                        </div>
                                    </div>
                                    ${h.insemination_planned ? `
                                        <button onclick="openInseminationForm(); document.getElementById('insemination-animal').value = '${h.animal_id}'; document.getElementById('insemination-date').value = '${h.heat_date}';" class="px-3 py-1 bg-pink-600 text-white text-xs rounded-lg hover:bg-pink-700">
                                            Inseminar
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum cio registrado</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar cios:', error);
                container.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar cios</div>';
            }
        }

        function openHeatForm() {
            document.getElementById('heat-modal').classList.remove('hidden');
            document.getElementById('heat-form').reset();
        }

        function closeHeatForm() {
            document.getElementById('heat-modal').classList.add('hidden');
        }

        document.getElementById('heat-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            data.insemination_planned = document.getElementById('heat-planned').checked ? 1 : 0;
            
            try {
                const response = await fetch(`${API_BASE}?action=heat_create`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Cio registrado com sucesso!');
                    }
                    closeHeatForm();
                    loadHeats();
                    loadDashboard();
                } else {
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast(result.error || 'Erro ao salvar cio');
                    }
                }
            } catch (error) {
                console.error('Erro ao salvar cio:', error);
                if (typeof window.showErrorToast === 'function') {
                    window.showErrorToast('Erro ao salvar cio');
                }
            }
        });

        // Partos
        async function loadBirths() {
            const container = document.getElementById('births-list');
            container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const response = await fetch(`${API_BASE}?action=births_list&limit=50`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const births = result.data.births || [];
                    
                    if (births.length > 0) {
                        container.innerHTML = births.map(b => `
                            <div class="p-4 bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow fade-in">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <p class="font-medium text-gray-900">${b.animal_number || 'N/A'} - ${b.animal_name || 'Sem nome'}</p>
                                            ${b.calf_gender ? `<span class="px-2 py-1 text-xs font-medium rounded-full ${b.calf_gender === 'macho' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'}">${b.calf_gender}</span>` : ''}
                                        </div>
                                        <div class="text-sm text-gray-600 space-y-1">
                                            <p><strong>Data:</strong> ${formatDate(b.birth_date)}</p>
                                            ${b.calf_weight ? `<p><strong>Peso:</strong> ${b.calf_weight} kg</p>` : ''}
                                            ${b.calf_status ? `<p><strong>Status:</strong> ${b.calf_status}</p>` : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum parto registrado</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar partos:', error);
                container.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar partos</div>';
            }
        }

        function openBirthForm() {
            document.getElementById('birth-modal').classList.remove('hidden');
            document.getElementById('birth-form').reset();
        }

        function closeBirthForm() {
            document.getElementById('birth-modal').classList.add('hidden');
        }

        document.getElementById('birth-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch(`${API_BASE}?action=birth_create`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Parto registrado com sucesso!');
                    }
                    closeBirthForm();
                    loadBirths();
                    loadDashboard();
                } else {
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast(result.error || 'Erro ao salvar parto');
                    }
                }
            } catch (error) {
                console.error('Erro ao salvar parto:', error);
                if (typeof window.showErrorToast === 'function') {
                    window.showErrorToast('Erro ao salvar parto');
                }
            }
        });

        // Funções auxiliares
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function getPregnancyStatusColor(status) {
            const colors = {
                'prenha': 'bg-green-100 text-green-800',
                'vazia': 'bg-red-100 text-red-800',
                'pendente': 'bg-yellow-100 text-yellow-800',
                'aborto': 'bg-gray-100 text-gray-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        function getPregnancyStatusText(status) {
            const texts = {
                'prenha': 'Prenha',
                'vazia': 'Vazia',
                'pendente': 'Pendente',
                'aborto': 'Aborto'
            };
            return texts[status] || status;
        }

        function getStageColor(stage) {
            const colors = {
                'inicial': 'bg-blue-100 text-blue-800',
                'meio': 'bg-yellow-100 text-yellow-800',
                'final': 'bg-orange-100 text-orange-800',
                'pre-parto': 'bg-red-100 text-red-800'
            };
            return colors[stage] || 'bg-gray-100 text-gray-800';
        }

        function getIntensityColor(intensity) {
            const colors = {
                'leve': 'bg-gray-100 text-gray-800',
                'moderado': 'bg-yellow-100 text-yellow-800',
                'forte': 'bg-red-100 text-red-800'
            };
            return colors[intensity] || 'bg-gray-100 text-gray-800';
        }

        // Fechar modais ao clicar fora
        document.querySelectorAll('[id$="-modal"]').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
