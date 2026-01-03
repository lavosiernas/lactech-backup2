<?php
/**
 * Página: Controle de Alimentação
 * Subpágina do Mais Opções - Sistema completo de controle de alimentação
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
    <title>Controle de Alimentação - LacTech</title>
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
        .modal-overlay {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
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
                <div class="w-10 h-10 bg-gradient-to-br from-lime-500 to-lime-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Controle de Alimentação</h2>
                    <p class="text-sm text-gray-500">Gerencie os registros de alimentação do rebanho</p>
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
            <!-- Card de Situação Nutricional (NOVO) -->
            <div class="mb-6 bg-gradient-to-br from-indigo-50 to-blue-50 rounded-xl p-6 border-2 border-indigo-200 fade-in">
                <div class="mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Situação Nutricional</h3>
                    <p class="text-sm text-gray-600">Comparação entre consumo real e ideal. Os pesos são cadastrados na página <strong>Grupos e Lotes</strong>.</p>
                </div>
                <div id="nutritional-situation-content" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <p class="text-sm text-gray-600 mb-1">Selecione um lote</p>
                        <select id="nutrition-situation-group" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500" onchange="loadNutritionalSituation()">
                            <option value="">Selecione um lote...</option>
                        </select>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-gray-200 text-center">
                        <p class="text-xs text-gray-600 mb-1">Status</p>
                        <p id="nutrition-status" class="text-2xl font-bold text-gray-400">-</p>
                        <p id="nutrition-status-label" class="text-xs text-gray-500 mt-1">Selecione um lote</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-gray-200 text-center">
                        <p class="text-xs text-gray-600 mb-1">Diferença</p>
                        <p id="nutrition-diff" class="text-2xl font-bold text-gray-400">-</p>
                        <p id="nutrition-diff-label" class="text-xs text-gray-500 mt-1">vs Ideal</p>
                    </div>
                </div>
                <div id="nutritional-details" class="mt-4 hidden">
                    <div class="bg-white rounded-lg p-4 border border-gray-200">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600">Ideal MS Total</p>
                                <p id="ideal-ms-total" class="font-semibold text-gray-900">-</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Real MS Total</p>
                                <p id="real-ms-total" class="font-semibold text-gray-900">-</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Peso Médio</p>
                                <p id="avg-weight" class="font-semibold text-gray-900">-</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Nº Animais</p>
                                <p id="animal-count-nutrition" class="font-semibold text-gray-900">-</p>
                            </div>
                        </div>
                        <div id="nutrition-alert" class="mt-3 p-3 rounded-lg hidden">
                            <p id="nutrition-alert-text" class="text-sm"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo Diário -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4" id="feeding-daily-summary">
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-green-700">Concentrado</span>
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-green-900" id="summary-concentrate">0 kg</p>
                    <p class="text-xs text-green-600 mt-1">Hoje</p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-700">Volumoso</span>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-900" id="summary-roughage">0 kg</p>
                    <p class="text-xs text-blue-600 mt-1">Hoje</p>
                </div>
                <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-4 border border-yellow-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-yellow-700">Silagem</span>
                        <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-yellow-900" id="summary-silage">0 kg</p>
                    <p class="text-xs text-yellow-600 mt-1">Hoje</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-purple-700">Animais</span>
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-purple-900" id="summary-animals">0</p>
                    <p class="text-xs text-purple-600 mt-1">Alimentados hoje</p>
                </div>
            </div>

            <!-- Filtros e Ações -->
            <div class="mb-6 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                <div class="flex flex-wrap gap-3">
                    <input type="date" id="feeding-filter-date-from" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" value="<?php echo date('Y-m-d', strtotime('-7 days')); ?>">
                    <input type="date" id="feeding-filter-date-to" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" value="<?php echo date('Y-m-d'); ?>">
                    <select id="feeding-filter-animal" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent min-w-[200px]">
                        <option value="">Todos os animais</option>
                    </select>
                    <button onclick="loadFeedingRecords()" class="px-4 py-2 bg-lime-600 text-white rounded-lg hover:bg-lime-700 transition-colors font-medium flex items-center">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Atualizar
                    </button>
                </div>
                <button onclick="openNewFeedForm()" class="px-5 py-2 bg-gradient-to-r from-lime-600 to-lime-700 text-white rounded-lg hover:from-lime-700 hover:to-lime-800 transition-all font-medium shadow-md flex items-center">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Registro
                </button>
            </div>

            <!-- Lista de Registros -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Data</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Animal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Turno</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Concentrado (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Volumoso (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Silagem (kg)</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Custo</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="feeding-records-list" class="divide-y divide-gray-200">
                            <tr>
                                    <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                        <p>Carregando registros...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Novo/Editar Registro -->
    <div id="feed-form-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" id="feed-form-title">Novo Registro de Alimentação</h3>
                    <button onclick="closeFeedForm()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="feed-form" class="p-6 space-y-4">
                    <input type="hidden" id="feed-form-id" name="id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Registro *</label>
                            <select id="feed-form-record-type" name="record_type" required onchange="handleRecordTypeChange()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                <option value="group">Por Lote</option>
                                <option value="individual">Por Animal Individual</option>
                            </select>
                        </div>
                        <div id="feed-form-group-container">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lote *</label>
                            <select id="feed-form-group" name="group_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" onchange="loadIdealForGroup()">
                                <option value="">Selecione um lote</option>
                            </select>
                        </div>
                        <div id="feed-form-animal-container" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Animal *</label>
                            <select id="feed-form-animal" name="animal_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                <option value="">Selecione um animal</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                            <input type="date" id="feed-form-date" name="feed_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Turno *</label>
                            <select id="feed-form-shift" name="shift" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                                <option value="unico">Único</option>
                                <option value="manha">Manhã</option>
                                <option value="tarde">Tarde</option>
                                <option value="noite">Noite</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Ração</label>
                            <input type="text" id="feed-form-type" name="feed_type" placeholder="Ex: Ração balanceada" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                            <input type="text" id="feed-form-brand" name="feed_brand" placeholder="Ex: NutriLeite" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">% Proteína</label>
                            <input type="number" id="feed-form-protein" name="protein_percentage" step="0.01" min="0" max="100" placeholder="Ex: 18.5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <!-- Indicador de Alimentação Ideal (NOVO) -->
                    <div id="ideal-feed-indicator" class="hidden border-t border-gray-200 pt-4">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-sm font-semibold text-blue-900">Alimentação Ideal Calculada</h4>
                                <button type="button" onclick="loadIdealForGroup()" class="text-xs text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Recalcular
                                </button>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                <div>
                                    <p class="text-blue-700 text-xs">Concentrado Ideal</p>
                                    <p id="ideal-concentrate" class="font-bold text-blue-900">-</p>
                                </div>
                                <div>
                                    <p class="text-blue-700 text-xs">Volumoso Ideal</p>
                                    <p id="ideal-roughage" class="font-bold text-blue-900">-</p>
                                </div>
                                <div>
                                    <p class="text-blue-700 text-xs">Silagem Ideal</p>
                                    <p id="ideal-silage" class="font-bold text-blue-900">-</p>
                                </div>
                                <div>
                                    <p class="text-blue-700 text-xs">MS Total Ideal</p>
                                    <p id="ideal-ms" class="font-bold text-blue-900">-</p>
                                </div>
                            </div>
                            <p id="ideal-weight-info" class="text-xs text-blue-600 mt-2"></p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Quantidades Fornecidas (kg)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Concentrado (kg) *</label>
                                <input type="number" id="feed-form-concentrate" name="concentrate_kg" step="0.01" min="0" required value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Volumoso (kg)</label>
                                <input type="number" id="feed-form-roughage" name="roughage_kg" step="0.01" min="0" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Silagem (kg)</label>
                                <input type="number" id="feed-form-silage" name="silage_kg" step="0.01" min="0" value="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Custos</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Custo por kg (R$)</label>
                                <input type="number" id="feed-form-cost-per-kg" name="cost_per_kg" step="0.01" min="0" placeholder="0.00" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent" onchange="calculateTotalCost()">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Custo Total (R$)</label>
                                <input type="number" id="feed-form-total-cost" name="total_cost" step="0.01" min="0" readonly class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50">
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea id="feed-form-notes" name="notes" rows="3" placeholder="Observações adicionais..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                        <input type="checkbox" id="feed-form-automatic" name="automatic" value="1" class="w-4 h-4 text-lime-600 border-gray-300 rounded focus:ring-lime-500">
                        <label for="feed-form-automatic" class="text-sm text-gray-700">Registro automático</label>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-lime-600 to-lime-700 text-white rounded-lg hover:from-lime-700 hover:to-lime-800 transition-all font-medium">
                            Salvar Registro
                        </button>
                        <button type="button" onclick="closeFeedForm()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/feed.php';
        const API_INTELLIGENCE = '../api/feed_intelligence.php';
        const API_GROUPS = '../api/groups.php';
        let currentEditId = null;
        let animalsList = [];
        let groupsList = [];
        let currentIdealCalculation = null;

        // Carregar animais ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadAnimals();
            loadGroups();
            loadDailySummary();
            loadFeedingRecords();
        });

        // Carregar lista de grupos/lotes
        async function loadGroups() {
            try {
                const response = await fetch(`${API_GROUPS}?action=list`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    groupsList = result.data;
                    
                    // Popular select do formulário
                    const formSelect = document.getElementById('feed-form-group');
                    if (formSelect) {
                        // Limpar opções existentes (exceto a primeira)
                        while (formSelect.children.length > 1) formSelect.removeChild(formSelect.lastChild);
                        
                        // Adicionar grupos
                        groupsList.forEach(group => {
                            const option = document.createElement('option');
                            option.value = group.id;
                            option.textContent = `${group.group_name}${group.group_code ? ' (' + group.group_code + ')' : ''}`;
                            formSelect.appendChild(option);
                        });
                    }
                    
                    // Popular select do card de situação nutricional
                    const nutritionSelect = document.getElementById('nutrition-situation-group');
                    if (nutritionSelect) {
                        // Limpar opções existentes (exceto a primeira)
                        while (nutritionSelect.children.length > 1) nutritionSelect.removeChild(nutritionSelect.lastChild);
                        
                        // Adicionar grupos
                        groupsList.forEach(group => {
                            const option = document.createElement('option');
                            option.value = group.id;
                            option.textContent = `${group.group_name}${group.group_code ? ' (' + group.group_code + ')' : ''}`;
                            nutritionSelect.appendChild(option);
                        });
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar grupos:', error);
            }
        }

        // Carregar lista de animais
        async function loadAnimals() {
            try {
                const response = await fetch(`${API_BASE}?action=animals`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    animalsList = result.data;
                    const select = document.getElementById('feeding-filter-animal');
                    const formSelect = document.getElementById('feed-form-animal');
                    
                    // Limpar opções existentes (exceto a primeira)
                    while (select.children.length > 1) select.removeChild(select.lastChild);
                    while (formSelect.children.length > 1) formSelect.removeChild(formSelect.lastChild);
                    
                    // Adicionar animais
                    animalsList.forEach(animal => {
                        const option1 = document.createElement('option');
                        option1.value = animal.id;
                        option1.textContent = `${animal.animal_number}${animal.name ? ' - ' + animal.name : ''}`;
                        select.appendChild(option1);
                        
                        const option2 = document.createElement('option');
                        option2.value = animal.id;
                        option2.textContent = `${animal.animal_number}${animal.name ? ' - ' + animal.name : ''}`;
                        formSelect.appendChild(option2);
                    });
                }
            } catch (error) {
                console.error('Erro ao carregar animais:', error);
            }
        }

        // Carregar resumo diário
        async function loadDailySummary() {
            try {
                const today = new Date().toISOString().split('T')[0];
                const response = await fetch(`${API_BASE}?action=daily_summary&date=${today}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const data = result.data;
                    document.getElementById('summary-concentrate').textContent = (data.total_concentrate || 0).toFixed(2) + ' kg';
                    document.getElementById('summary-roughage').textContent = (data.total_roughage || 0).toFixed(2) + ' kg';
                    document.getElementById('summary-silage').textContent = (data.total_silage || 0).toFixed(2) + ' kg';
                    document.getElementById('summary-animals').textContent = data.total_animals_fed || 0;
                }
            } catch (error) {
                console.error('Erro ao carregar resumo:', error);
            }
        }

        // Carregar registros de alimentação
        async function loadFeedingRecords() {
            const tbody = document.getElementById('feeding-records-list');
            tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando registros...</p></div></td></tr>';
            
            try {
                const dateFrom = document.getElementById('feeding-filter-date-from').value;
                const dateTo = document.getElementById('feeding-filter-date-to').value;
                const animalId = document.getElementById('feeding-filter-animal').value;
                
                let url = `${API_BASE}?action=list&date_from=${dateFrom}&date_to=${dateTo}`;
                if (animalId) url += `&animal_id=${animalId}`;
                
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(record => {
                        const isGroup = record.record_type === 'group' || record.group_id;
                        const animalDisplay = isGroup 
                            ? `<div class="font-medium">Lote: ${record.group_name || 'N/A'}</div><div class="text-xs text-gray-500">${record.animal_count || 0} animais</div>`
                            : `<div class="font-medium">${record.animal_number || 'N/A'}</div>${record.animal_name ? `<div class="text-xs text-gray-500">${record.animal_name}</div>` : ''}`;
                        
                        return `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900">${formatDate(record.feed_date)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                ${animalDisplay}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">${formatShift(record.shift)}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900">${parseFloat(record.concentrate_kg || 0).toFixed(2)}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900">${parseFloat(record.roughage_kg || 0).toFixed(2)}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900">${parseFloat(record.silage_kg || 0).toFixed(2)}</td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900">
                                ${record.total_cost ? 'R$ ' + parseFloat(record.total_cost).toFixed(2) : '-'}
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="editFeedRecord(${record.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteFeedRecord(${record.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>`;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg><p>Nenhum registro encontrado</p></div></td></tr>';
                }
            } catch (error) {
                console.error('Erro ao carregar registros:', error);
                tbody.innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-red-500">Erro ao carregar registros</td></tr>';
            }
        }

        // Abrir formulário de novo registro
        function openNewFeedForm() {
            currentEditId = null;
            currentIdealCalculation = null;
            document.getElementById('feed-form-title').textContent = 'Novo Registro de Alimentação';
            document.getElementById('feed-form').reset();
            document.getElementById('feed-form-id').value = '';
            document.getElementById('feed-form-record-type').value = 'group';
            handleRecordTypeChange();
            document.getElementById('feed-form-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('feed-form-concentrate').value = '0';
            document.getElementById('feed-form-roughage').value = '0';
            document.getElementById('feed-form-silage').value = '0';
            document.getElementById('feed-form-total-cost').value = '';
            document.getElementById('ideal-feed-indicator').classList.add('hidden');
            document.getElementById('feed-form-modal').classList.remove('hidden');
        }

        // Fechar formulário
        function closeFeedForm() {
            document.getElementById('feed-form-modal').classList.add('hidden');
            currentEditId = null;
        }

        // Editar registro
        async function editFeedRecord(id) {
            try {
                const response = await fetch(`${API_BASE}?action=get&id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const record = result.data;
                    currentEditId = id;
                    currentIdealCalculation = null;
                    
                    document.getElementById('feed-form-title').textContent = 'Editar Registro de Alimentação';
                    document.getElementById('feed-form-id').value = record.id;
                    
                    // Determinar tipo de registro
                    const recordType = record.record_type || (record.group_id ? 'group' : 'individual');
                    document.getElementById('feed-form-record-type').value = recordType;
                    handleRecordTypeChange();
                    
                    if (recordType === 'group') {
                        document.getElementById('feed-form-group').value = record.group_id || '';
                    } else {
                        document.getElementById('feed-form-animal').value = record.animal_id || '';
                    }
                    
                    document.getElementById('feed-form-date').value = record.feed_date;
                    document.getElementById('feed-form-shift').value = record.shift;
                    document.getElementById('feed-form-type').value = record.feed_type || '';
                    document.getElementById('feed-form-brand').value = record.feed_brand || '';
                    document.getElementById('feed-form-protein').value = record.protein_percentage || '';
                    document.getElementById('feed-form-concentrate').value = record.concentrate_kg || '0';
                    document.getElementById('feed-form-roughage').value = record.roughage_kg || '0';
                    document.getElementById('feed-form-silage').value = record.silage_kg || '0';
                    document.getElementById('feed-form-cost-per-kg').value = record.cost_per_kg || '';
                    document.getElementById('feed-form-total-cost').value = record.total_cost || '';
                    document.getElementById('feed-form-notes').value = record.notes || '';
                    document.getElementById('feed-form-automatic').checked = record.automatic == 1;
                    
                    document.getElementById('ideal-feed-indicator').classList.add('hidden');
                    document.getElementById('feed-form-modal').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar registro: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao editar registro:', error);
                alert('Erro ao carregar registro');
            }
        }

        // Deletar registro
        async function deleteFeedRecord(id) {
            if (!confirm('Tem certeza que deseja excluir este registro?')) return;
            
            try {
                const response = await fetch(`${API_BASE}?action=delete&id=${id}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    loadFeedingRecords();
                    loadDailySummary();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Registro excluído com sucesso!');
                    } else {
                        alert('Registro excluído com sucesso!');
                    }
                } else {
                    alert('Erro ao excluir: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao deletar registro:', error);
                alert('Erro ao excluir registro');
            }
        }

        // Calcular custo total
        function calculateTotalCost() {
            const costPerKg = parseFloat(document.getElementById('feed-form-cost-per-kg').value) || 0;
            const concentrate = parseFloat(document.getElementById('feed-form-concentrate').value) || 0;
            const roughage = parseFloat(document.getElementById('feed-form-roughage').value) || 0;
            const silage = parseFloat(document.getElementById('feed-form-silage').value) || 0;
            
            const totalKg = concentrate + roughage + silage;
            const totalCost = totalKg * costPerKg;
            
            document.getElementById('feed-form-total-cost').value = totalCost > 0 ? totalCost.toFixed(2) : '';
        }

        // Adicionar listeners para calcular custo automaticamente
        ['feed-form-concentrate', 'feed-form-roughage', 'feed-form-silage', 'feed-form-cost-per-kg'].forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.addEventListener('input', calculateTotalCost);
            }
        });

        // Funções auxiliares
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('pt-BR');
        }

        function formatShift(shift) {
            const shifts = {
                'manha': 'Manhã',
                'tarde': 'Tarde',
                'noite': 'Noite',
                'unico': 'Único'
            };
            return shifts[shift] || shift;
        }

        // Fechar modal ao clicar fora
        document.getElementById('feed-form-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeFeedForm();
            }
        });

        // ==========================================
        // FUNÇÕES PARA SISTEMA DE INTELIGÊNCIA
        // ==========================================

        // Trocar entre registro por lote ou individual
        function handleRecordTypeChange() {
            const recordType = document.getElementById('feed-form-record-type').value;
            const groupContainer = document.getElementById('feed-form-group-container');
            const animalContainer = document.getElementById('feed-form-animal-container');
            const groupSelect = document.getElementById('feed-form-group');
            const animalSelect = document.getElementById('feed-form-animal');
            
            if (recordType === 'group') {
                groupContainer.classList.remove('hidden');
                animalContainer.classList.add('hidden');
                groupSelect.required = true;
                animalSelect.required = false;
                animalSelect.value = '';
            } else {
                groupContainer.classList.add('hidden');
                animalContainer.classList.remove('hidden');
                groupSelect.required = false;
                animalSelect.required = true;
                groupSelect.value = '';
                document.getElementById('ideal-feed-indicator').classList.add('hidden');
            }
        }

        // Carregar alimentação ideal para o lote selecionado
        async function loadIdealForGroup() {
            const groupId = document.getElementById('feed-form-group').value;
            if (!groupId) {
                document.getElementById('ideal-feed-indicator').classList.add('hidden');
                return;
            }

            try {
                // Buscar informações do grupo (para obter número de animais)
                const groupInfo = groupsList.find(g => g.id == groupId);
                
                const response = await fetch(`${API_INTELLIGENCE}?action=calculate_ideal_group&group_id=${groupId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const ideal = result.data.ideal;
                    currentIdealCalculation = result.data;
                    
                    document.getElementById('ideal-concentrate').textContent = ideal.concentrate_kg.toFixed(2) + ' kg';
                    document.getElementById('ideal-roughage').textContent = ideal.roughage_kg.toFixed(2) + ' kg';
                    document.getElementById('ideal-silage').textContent = ideal.silage_kg.toFixed(2) + ' kg';
                    document.getElementById('ideal-ms').textContent = ideal.ms_total_kg.toFixed(2) + ' kg';
                    
                    const weightInfo = `Peso médio: ${result.data.avg_weight_kg} kg × ${result.data.animal_count} animais = ${(result.data.avg_weight_kg * result.data.animal_count).toFixed(0)} kg total`;
                    document.getElementById('ideal-weight-info').textContent = weightInfo;
                    
                    // Preencher automaticamente os campos de quantidades fornecidas
                    document.getElementById('feed-form-concentrate').value = ideal.concentrate_kg.toFixed(2);
                    document.getElementById('feed-form-roughage').value = ideal.roughage_kg.toFixed(2);
                    document.getElementById('feed-form-silage').value = ideal.silage_kg.toFixed(2);
                    
                    // Recalcular custo total se houver custo por kg
                    calculateTotalCost();
                    
                    document.getElementById('ideal-feed-indicator').classList.remove('hidden');
                } else {
                    document.getElementById('ideal-feed-indicator').classList.add('hidden');
                    if (result.error) {
                        console.error('Erro ao calcular ideal:', result.error);
                        if (typeof window.showErrorToast === 'function') {
                            window.showErrorToast('Erro ao calcular ideal: ' + result.error);
                        }
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar ideal:', error);
                document.getElementById('ideal-feed-indicator').classList.add('hidden');
            }
        }

        // Carregar situação nutricional
        async function loadNutritionalSituation() {
            const groupId = document.getElementById('nutrition-situation-group').value;
            if (!groupId) {
                document.getElementById('nutrition-status').textContent = '-';
                document.getElementById('nutrition-status-label').textContent = 'Selecione um lote';
                document.getElementById('nutrition-diff').textContent = '-';
                document.getElementById('nutritional-details').classList.add('hidden');
                return;
            }

            try {
                // Buscar peso do lote
                const weightResponse = await fetch(`${API_INTELLIGENCE}?action=get_group_average_weight&group_id=${groupId}`);
                const weightResult = await weightResponse.json();
                
                if (weightResult.success && weightResult.data) {
                    const weightData = weightResult.data;
                    document.getElementById('avg-weight').textContent = weightData.avg_weight_kg.toFixed(2) + ' kg';
                    document.getElementById('animal-count-nutrition').textContent = weightData.animal_count || '-';
                }

                // Calcular ideal
                const idealResponse = await fetch(`${API_INTELLIGENCE}?action=calculate_ideal_group&group_id=${groupId}`);
                const idealResult = await idealResponse.json();
                
                if (idealResult.success && idealResult.data) {
                    const ideal = idealResult.data.ideal;
                    document.getElementById('ideal-ms-total').textContent = ideal.ms_total_kg.toFixed(2) + ' kg';
                    
                    // Buscar registros reais do lote hoje
                    const today = new Date().toISOString().split('T')[0];
                    const feedResponse = await fetch(`${API_BASE}?action=list&date_from=${today}&date_to=${today}`);
                    const feedResult = await feedResponse.json();
                    
                    if (feedResult.success && feedResult.data) {
                        // Filtrar registros do lote
                        const groupRecords = feedResult.data.filter(r => r.group_id == groupId);
                        
                        // Calcular MS real (usando % MS padrão)
                        let realMsTotal = 0;
                        groupRecords.forEach(r => {
                            const msConcentrate = (r.concentrate_kg || 0) * 0.88;
                            const msRoughage = (r.roughage_kg || 0) * 0.25;
                            const msSilage = (r.silage_kg || 0) * 0.35;
                            realMsTotal += msConcentrate + msRoughage + msSilage;
                        });
                        
                        document.getElementById('real-ms-total').textContent = realMsTotal.toFixed(2) + ' kg';
                        
                        const diff = realMsTotal - ideal.ms_total_kg;
                        const diffPct = ideal.ms_total_kg > 0 ? (diff / ideal.ms_total_kg) * 100 : 0;
                        
                        document.getElementById('nutrition-diff').textContent = (diffPct >= 0 ? '+' : '') + diffPct.toFixed(1) + '%';
                        
                        // Status
                        let status = 'OK';
                        let statusColor = 'text-green-600';
                        let statusLabel = 'Dentro do ideal';
                        let alertClass = 'bg-green-50 border-green-200 text-green-800';
                        let alertText = '';
                        
                        if (diffPct < -15) {
                            status = 'ABAIXO';
                            statusColor = 'text-red-600';
                            statusLabel = 'Abaixo do ideal';
                            alertClass = 'bg-red-50 border-red-200 text-red-800';
                            alertText = 'Consumo abaixo do ideal. Considere aumentar a quantidade fornecida.';
                        } else if (diffPct > 15) {
                            status = 'ACIMA';
                            statusColor = 'text-orange-600';
                            statusLabel = 'Acima do ideal';
                            alertClass = 'bg-orange-50 border-orange-200 text-orange-800';
                            alertText = 'Consumo acima do ideal. Verifique se não há desperdício.';
                        } else if (Math.abs(diffPct) > 10) {
                            status = 'ATENÇÃO';
                            statusColor = 'text-yellow-600';
                            statusLabel = 'Próximo ao limite';
                            alertClass = 'bg-yellow-50 border-yellow-200 text-yellow-800';
                            alertText = 'Consumo próximo ao limite. Monitore o desempenho dos animais.';
                        }
                        
                        document.getElementById('nutrition-status').textContent = status;
                        document.getElementById('nutrition-status').className = `text-2xl font-bold ${statusColor}`;
                        document.getElementById('nutrition-status-label').textContent = statusLabel;
                        
                        if (alertText) {
                            const alertDiv = document.getElementById('nutrition-alert');
                            alertDiv.className = `mt-3 p-3 rounded-lg ${alertClass}`;
                            document.getElementById('nutrition-alert-text').textContent = alertText;
                            alertDiv.classList.remove('hidden');
                        } else {
                            document.getElementById('nutrition-alert').classList.add('hidden');
                        }
                        
                        document.getElementById('nutritional-details').classList.remove('hidden');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar situação nutricional:', error);
            }
        }

        // Modificar submit do formulário de alimentação para incluir group_id
        const feedFormSubmit = document.getElementById('feed-form').onsubmit;
        document.getElementById('feed-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (key === 'automatic') {
                    data[key] = document.getElementById('feed-form-automatic').checked ? 1 : 0;
                } else if (value !== '') {
                    data[key] = value;
                }
            });
            
            // Adicionar record_type e group_id se for lote
            const recordType = document.getElementById('feed-form-record-type').value;
            if (recordType === 'group') {
                data.record_type = 'group';
                data.group_id = document.getElementById('feed-form-group').value;
                data.animal_id = null;
            } else {
                data.record_type = 'individual';
                data.group_id = null;
            }
            
            // Converter valores numéricos
            ['concentrate_kg', 'roughage_kg', 'silage_kg', 'protein_percentage', 'cost_per_kg', 'total_cost'].forEach(key => {
                if (data[key] !== undefined && data[key] !== '') {
                    data[key] = parseFloat(data[key]);
                }
            });
            
            try {
                const url = currentEditId 
                    ? `${API_BASE}?action=update`
                    : `${API_BASE}?action=create`;
                
                const method = currentEditId ? 'PUT' : 'POST';
                
                if (currentEditId) {
                    data.id = currentEditId;
                }
                
                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeFeedForm();
                    loadFeedingRecords();
                    loadDailySummary();
                    loadNutritionalSituation();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Registro salvo com sucesso!');
                    } else {
                        alert('Registro salvo com sucesso!');
                    }
                } else {
                    alert('Erro ao salvar: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao salvar registro:', error);
                alert('Erro ao salvar registro');
            }
        });
    </script>
</body>
</html>
