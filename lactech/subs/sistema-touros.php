<?php
/**
 * Página: Sistema de Touros
 * Subpágina do Mais Opções - Sistema completo de gestão de touros
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
    <title>Sistema de Touros - LacTech</title>
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
        .tab-button {
            transition: all 0.2s;
        }
        .tab-button.active {
            background: linear-gradient(to right, #dc2626, #b91c1c);
            color: white;
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
                <div class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                        <circle cx="12" cy="8" r="2" fill="white"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Sistema de Touros</h2>
                    <p class="text-sm text-gray-500">Gerencie touros, coberturas e sêmen</p>
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
            <!-- Estatísticas -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4" id="bulls-statistics">
                <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 border border-red-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-red-700">Total de Touros</span>
                        <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5zM9 16c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm6 0c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-red-900" id="stat-total">0</p>
                    <p class="text-xs text-red-600 mt-1">Cadastrados</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-green-700">Ativos</span>
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-green-900" id="stat-active">0</p>
                    <p class="text-xs text-green-600 mt-1">Em reprodução</p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-700">Taxa de Sucesso</span>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-900" id="stat-efficiency">0%</p>
                    <p class="text-xs text-blue-600 mt-1">Média geral</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-purple-700">Sêmen Disponível</span>
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-purple-900" id="stat-semen">0</p>
                    <p class="text-xs text-purple-600 mt-1">Palhetas</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6 flex gap-2 border-b border-gray-200">
                <button onclick="switchTab('bulls')" class="tab-button active px-6 py-3 font-medium text-gray-700 hover:text-gray-900 border-b-2 border-transparent hover:border-red-600 transition-colors" id="tab-bulls">
                    Touros
                </button>
                <button onclick="switchTab('coatings')" class="tab-button px-6 py-3 font-medium text-gray-700 hover:text-gray-900 border-b-2 border-transparent hover:border-red-600 transition-colors" id="tab-coatings">
                    Coberturas
                </button>
                <button onclick="switchTab('semen')" class="tab-button px-6 py-3 font-medium text-gray-700 hover:text-gray-900 border-b-2 border-transparent hover:border-red-600 transition-colors" id="tab-semen">
                    Sêmen
                </button>
                <button onclick="switchTab('reports')" class="tab-button px-6 py-3 font-medium text-gray-700 hover:text-gray-900 border-b-2 border-transparent hover:border-red-600 transition-colors" id="tab-reports">
                    Relatórios
                </button>
            </div>

            <!-- Tab Content: Touros -->
            <div id="content-bulls" class="tab-content">
                <!-- Filtros -->
                <div class="mb-6 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    <div class="flex flex-wrap gap-3">
                        <input type="text" id="bulls-search" placeholder="Buscar por número, nome..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent min-w-[250px]">
                        <select id="bulls-filter-breed" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="">Todas as raças</option>
                        </select>
                        <select id="bulls-filter-status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            <option value="">Todos os status</option>
                            <option value="ativo">Ativo</option>
                            <option value="em_reproducao">Em Reprodução</option>
                            <option value="reserva">Reserva</option>
                            <option value="inativo">Inativo</option>
                            <option value="descartado">Descartado</option>
                        </select>
                        <button onclick="loadBulls()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium flex items-center">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Buscar
                        </button>
                    </div>
                    <button onclick="openBullForm()" class="px-5 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-medium shadow-md flex items-center">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Novo Touro
                    </button>
                </div>

                <!-- Lista de Touros -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Número</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Raça</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Idade</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Inseminações</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Coberturas</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Eficiência</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="bulls-list" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            <p>Carregando touros...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Coberturas -->
            <div id="content-coatings" class="tab-content hidden">
                <div class="mb-6 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Registros de Cobertura</h3>
                    <button onclick="openCoatingForm()" class="px-5 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-medium shadow-md flex items-center">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Nova Cobertura
                    </button>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Touro</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Vaca</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Tipo</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Resultado</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="coatings-list" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Carregando coberturas...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Sêmen -->
            <div id="content-semen" class="tab-content hidden">
                <div class="mb-6 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Catálogo de Sêmen</h3>
                    <button onclick="openSemenForm()" class="px-5 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-medium shadow-md flex items-center">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Novo Lote
                    </button>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Lote</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Touro</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Disponível</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Usado</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Validade</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Status</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="semen-list" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">Carregando sêmen...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Relatórios -->
            <div id="content-reports" class="tab-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Ranking de Eficiência</h3>
                        <div id="ranking-list" class="space-y-2">
                            <p class="text-gray-500 text-center">Carregando ranking...</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Alertas</h3>
                        <div id="alerts-list" class="space-y-2">
                            <p class="text-gray-500 text-center">Carregando alertas...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Touro -->
    <div id="bull-form-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" id="bull-form-title">Novo Touro</h3>
                    <button onclick="closeBullForm()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="bull-form" class="p-6 space-y-6">
                    <input type="hidden" id="bull-form-id" name="id">
                    
                    <!-- Identificação -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Identificação</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Número do Touro *</label>
                                <input type="text" id="bull-form-number" name="bull_number" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                                <input type="text" id="bull-form-name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Raça *</label>
                                <input type="text" id="bull-form-breed" name="breed" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento *</label>
                                <input type="date" id="bull-form-birth" name="birth_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código RFID</label>
                                <input type="text" id="bull-form-rfid" name="rfid_code" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Número do Brinco</label>
                                <input type="text" id="bull-form-earring" name="earring_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Status e Origem -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Status e Origem</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select id="bull-form-status" name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                    <option value="ativo">Ativo</option>
                                    <option value="em_reproducao">Em Reprodução</option>
                                    <option value="reserva">Reserva</option>
                                    <option value="inativo">Inativo</option>
                                    <option value="descartado">Descartado</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Origem</label>
                                <select id="bull-form-source" name="source" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                                    <option value="proprio">Próprio</option>
                                    <option value="comprado">Comprado</option>
                                    <option value="arrendado">Arrendado</option>
                                    <option value="inseminacao">Inseminação</option>
                                    <option value="alugado">Alugado</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Peso (kg)</label>
                                <input type="number" id="bull-form-weight" name="weight" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Escore Corporal (1-5)</label>
                                <input type="number" id="bull-form-body-score" name="body_score" step="0.1" min="1" max="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Informações Genéticas -->
                    <div class="border-b border-gray-200 pb-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações Genéticas</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Código Genético</label>
                                <input type="text" id="bull-form-genetic-code" name="genetic_code" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Pai (Sire)</label>
                                <input type="text" id="bull-form-sire" name="sire" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mãe (Dam)</label>
                                <input type="text" id="bull-form-dam" name="dam" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mérito Genético</label>
                                <input type="number" id="bull-form-genetic-merit" name="genetic_merit" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                        <textarea id="bull-form-notes" name="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-medium">
                            Salvar Touro
                        </button>
                        <button type="button" onclick="closeBullForm()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/bulls.php';
        let currentTab = 'bulls';
        let currentEditId = null;

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadStatistics();
            loadBulls();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Busca em tempo real
            const searchInput = document.getElementById('bulls-search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (currentTab === 'bulls') {
                            loadBulls();
                        }
                    }, 500);
                });
            }

            // Filtros
            ['bulls-filter-breed', 'bulls-filter-status'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('change', () => {
                        if (currentTab === 'bulls') loadBulls();
                    });
                }
            });
        }

        // Trocar de tab
        function switchTab(tab) {
            currentTab = tab;
            
            // Atualizar botões
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`tab-${tab}`).classList.add('active');
            
            // Atualizar conteúdo
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });
            document.getElementById(`content-${tab}`).classList.remove('hidden');
            
            // Carregar dados da tab
            switch(tab) {
                case 'bulls':
                    loadBulls();
                    break;
                case 'coatings':
                    loadCoatings();
                    break;
                case 'semen':
                    loadSemen();
                    break;
                case 'reports':
                    loadReports();
                    break;
            }
        }

        // Carregar estatísticas
        async function loadStatistics() {
            try {
                const response = await fetch(`${API_BASE}?action=statistics`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const stats = result.data;
                    document.getElementById('stat-total').textContent = stats.total_bulls || 0;
                    document.getElementById('stat-active').textContent = stats.active_bulls || 0;
                    document.getElementById('stat-efficiency').textContent = (stats.avg_efficiency || 0).toFixed(1) + '%';
                    document.getElementById('stat-semen').textContent = (stats.semen?.total_available || 0);
                }
            } catch (error) {
                console.error('Erro ao carregar estatísticas:', error);
            }
        }

        // Carregar touros
        async function loadBulls() {
            const tbody = document.getElementById('bulls-list');
            tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div></td></tr>';
            
            try {
                const search = document.getElementById('bulls-search').value;
                const breed = document.getElementById('bulls-filter-breed').value;
                const status = document.getElementById('bulls-filter-status').value;
                
                let url = `${API_BASE}?action=list`;
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (breed) url += `&breed=${encodeURIComponent(breed)}`;
                if (status) url += `&status=${encodeURIComponent(status)}`;
                
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(bull => `
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${bull.bull_number || 'N/A'}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">${bull.name || '-'}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">${bull.breed || '-'}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">${bull.age || 0} anos</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(bull.status)}">${formatStatus(bull.status)}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center text-gray-700">${bull.total_inseminations || 0}</td>
                            <td class="px-4 py-3 text-sm text-center text-gray-700">${bull.total_coatings || 0}</td>
                            <td class="px-4 py-3 text-sm text-center">
                                <span class="font-medium ${bull.efficiency_rate >= 70 ? 'text-green-600' : bull.efficiency_rate >= 50 ? 'text-yellow-600' : 'text-red-600'}">
                                    ${bull.efficiency_rate || 0}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="viewBullDetails(${bull.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Ver Detalhes">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="editBull(${bull.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteBull(${bull.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg><p>Nenhum touro encontrado</p></div></td></tr>';
                }
            } catch (error) {
                console.error('Erro ao carregar touros:', error);
                tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-red-500">Erro ao carregar touros</td></tr>';
            }
        }

        // Abrir formulário de novo touro
        function openBullForm() {
            currentEditId = null;
            document.getElementById('bull-form-title').textContent = 'Novo Touro';
            document.getElementById('bull-form').reset();
            document.getElementById('bull-form-id').value = '';
            document.getElementById('bull-form-status').value = 'ativo';
            document.getElementById('bull-form-source').value = 'proprio';
            document.getElementById('bull-form-modal').classList.remove('hidden');
        }

        // Fechar formulário
        function closeBullForm() {
            document.getElementById('bull-form-modal').classList.add('hidden');
            currentEditId = null;
        }

        // Editar touro
        async function editBull(id) {
            try {
                const response = await fetch(`${API_BASE}?action=get&id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const bull = result.data;
                    currentEditId = id;
                    
                    document.getElementById('bull-form-title').textContent = 'Editar Touro';
                    document.getElementById('bull-form-id').value = bull.id;
                    document.getElementById('bull-form-number').value = bull.bull_number || '';
                    document.getElementById('bull-form-name').value = bull.name || '';
                    document.getElementById('bull-form-breed').value = bull.breed || '';
                    document.getElementById('bull-form-birth').value = bull.birth_date || '';
                    document.getElementById('bull-form-rfid').value = bull.rfid_code || '';
                    document.getElementById('bull-form-earring').value = bull.earring_number || '';
                    document.getElementById('bull-form-status').value = bull.status || 'ativo';
                    document.getElementById('bull-form-source').value = bull.source || 'proprio';
                    document.getElementById('bull-form-weight').value = bull.weight || bull.current_weight || '';
                    document.getElementById('bull-form-body-score').value = bull.body_score || '';
                    document.getElementById('bull-form-genetic-code').value = bull.genetic_code || '';
                    document.getElementById('bull-form-sire').value = bull.sire || '';
                    document.getElementById('bull-form-dam').value = bull.dam || '';
                    document.getElementById('bull-form-genetic-merit').value = bull.genetic_merit || '';
                    document.getElementById('bull-form-notes').value = bull.notes || '';
                    
                    document.getElementById('bull-form-modal').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar touro: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao editar touro:', error);
                alert('Erro ao carregar touro');
            }
        }

        // Deletar touro
        async function deleteBull(id) {
            if (!confirm('Tem certeza que deseja excluir este touro?')) return;
            
            try {
                const response = await fetch(`${API_BASE}?action=delete&id=${id}`, {
                    method: 'DELETE'
                });
                const result = await response.json();
                
                if (result.success) {
                    loadBulls();
                    loadStatistics();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Touro excluído com sucesso!');
                    } else {
                        alert('Touro excluído com sucesso!');
                    }
                } else {
                    alert('Erro ao excluir: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao deletar touro:', error);
                alert('Erro ao excluir touro');
            }
        }

        // Ver detalhes do touro
        function viewBullDetails(id) {
            // Implementar modal de detalhes completo
            editBull(id); // Por enquanto, abre o formulário de edição
        }

        // Submeter formulário
        document.getElementById('bull-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (value !== '') {
                    data[key] = value;
                }
            });
            
            // Converter valores numéricos
            ['weight', 'body_score', 'genetic_merit'].forEach(key => {
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
                    closeBullForm();
                    loadBulls();
                    loadStatistics();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Touro salvo com sucesso!');
                    } else {
                        alert('Touro salvo com sucesso!');
                    }
                } else {
                    alert('Erro ao salvar: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao salvar touro:', error);
                alert('Erro ao salvar touro');
            }
        });

        // Carregar coberturas
        async function loadCoatings() {
            const tbody = document.getElementById('coatings-list');
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Carregando...</td></tr>';
            
            try {
                const response = await fetch(`${API_BASE}?action=coatings_list`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.coatings) {
                    if (result.data.coatings.length > 0) {
                        tbody.innerHTML = result.data.coatings.map(coating => `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">${formatDate(coating.coating_date)}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${coating.bull_id || '-'}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${coating.animal_number || '-'} ${coating.cow_name || ''}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${formatCoatingType(coating.coating_type)}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${getResultColor(coating.result)}">${formatResult(coating.result)}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <button onclick="editCoating(${coating.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nenhuma cobertura encontrada</td></tr>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar coberturas:', error);
            }
        }

        // Carregar sêmen
        async function loadSemen() {
            const tbody = document.getElementById('semen-list');
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Carregando...</td></tr>';
            
            try {
                const response = await fetch(`${API_BASE}?action=semen_list`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.semen) {
                    if (result.data.semen.length > 0) {
                        tbody.innerHTML = result.data.semen.map(s => `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">${s.batch_number || '-'}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${s.bull_number || '-'} ${s.bull_name || ''}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-700">${s.straws_available || 0}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-700">${s.straws_used || 0}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${formatDate(s.expiry_date)}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${getValidityColor(s.validity_status)}">${formatValidity(s.validity_status)}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <button onclick="viewSemenDetails(${s.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Nenhum lote de sêmen encontrado</td></tr>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar sêmen:', error);
            }
        }

        // Carregar relatórios
        async function loadReports() {
            // Ranking
            try {
                const response = await fetch(`${API_BASE}?action=ranking&limit=10`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.ranking) {
                    const rankingDiv = document.getElementById('ranking-list');
                    if (result.data.ranking.length > 0) {
                        rankingDiv.innerHTML = result.data.ranking.map((bull, index) => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold text-gray-400">#${index + 1}</span>
                                    <div>
                                        <p class="font-medium text-gray-900">${bull.bull_number || 'N/A'}</p>
                                        <p class="text-xs text-gray-500">${bull.name || ''}</p>
                                    </div>
                                </div>
                                <span class="font-bold ${bull.efficiency_rate >= 70 ? 'text-green-600' : 'text-yellow-600'}">${bull.efficiency_rate || 0}%</span>
                            </div>
                        `).join('');
                    } else {
                        rankingDiv.innerHTML = '<p class="text-gray-500 text-center">Nenhum dado disponível</p>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar ranking:', error);
            }

            // Alertas
            try {
                const response = await fetch(`${API_BASE}?action=alerts`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const alertsDiv = document.getElementById('alerts-list');
                    const alerts = [];
                    
                    if (result.data.semen_expiry && result.data.semen_expiry.length > 0) {
                        result.data.semen_expiry.forEach(s => {
                            alerts.push({
                                type: 'warning',
                                message: `Sêmen ${s.batch_number} vence em ${s.days_until_expiry} dias`
                            });
                        });
                    }
                    
                    if (result.data.low_efficiency && result.data.low_efficiency.length > 0) {
                        result.data.low_efficiency.forEach(b => {
                            alerts.push({
                                type: 'error',
                                message: `Touro ${b.bull_number} com baixa eficiência (${b.efficiency_rate || 0}%)`
                            });
                        });
                    }
                    
                    if (alerts.length > 0) {
                        alertsDiv.innerHTML = alerts.map(alert => `
                            <div class="p-3 rounded-lg ${alert.type === 'error' ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200'}">
                                <p class="text-sm ${alert.type === 'error' ? 'text-red-800' : 'text-yellow-800'}">${alert.message}</p>
                            </div>
                        `).join('');
                    } else {
                        alertsDiv.innerHTML = '<p class="text-gray-500 text-center">Nenhum alerta</p>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar alertas:', error);
            }
        }

        // Funções auxiliares
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('pt-BR');
        }

        function formatStatus(status) {
            const statusMap = {
                'ativo': 'Ativo',
                'em_reproducao': 'Em Reprodução',
                'reserva': 'Reserva',
                'inativo': 'Inativo',
                'descartado': 'Descartado'
            };
            return statusMap[status] || status;
        }

        function getStatusColor(status) {
            const colors = {
                'ativo': 'bg-green-100 text-green-800',
                'em_reproducao': 'bg-blue-100 text-blue-800',
                'reserva': 'bg-yellow-100 text-yellow-800',
                'inativo': 'bg-gray-100 text-gray-800',
                'descartado': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        function formatCoatingType(type) {
            const types = {
                'natural': 'Natural',
                'monta_direta': 'Monta Direta',
                'monta_controlada': 'Monta Controlada'
            };
            return types[type] || type;
        }

        function formatResult(result) {
            const results = {
                'prenhez': 'Prenhez',
                'vazia': 'Vazia',
                'aborto': 'Aborto',
                'pendente': 'Pendente'
            };
            return results[result] || result;
        }

        function getResultColor(result) {
            const colors = {
                'prenhez': 'bg-green-100 text-green-800',
                'vazia': 'bg-red-100 text-red-800',
                'aborto': 'bg-orange-100 text-orange-800',
                'pendente': 'bg-yellow-100 text-yellow-800'
            };
            return colors[result] || 'bg-gray-100 text-gray-800';
        }

        function formatValidity(status) {
            const statusMap = {
                'valido': 'Válido',
                'proximo_vencimento': 'Vencendo',
                'vencido': 'Vencido'
            };
            return statusMap[status] || status;
        }

        function getValidityColor(status) {
            const colors = {
                'valido': 'bg-green-100 text-green-800',
                'proximo_vencimento': 'bg-yellow-100 text-yellow-800',
                'vencido': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        // Placeholders para funções futuras
        function openCoatingForm() {
            alert('Funcionalidade de nova cobertura será implementada');
        }

        function editCoating(id) {
            alert('Funcionalidade de editar cobertura será implementada');
        }

        function openSemenForm() {
            alert('Funcionalidade de novo lote de sêmen será implementada');
        }

        function viewSemenDetails(id) {
            alert('Funcionalidade de detalhes de sêmen será implementada');
        }

        // Fechar modal ao clicar fora
        document.getElementById('bull-form-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeBullForm();
            }
        });
    </script>
</body>
</html>
