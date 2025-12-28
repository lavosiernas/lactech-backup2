<?php
/**
 * Página: Controle de Novilhas
 * Subpágina do Mais Opções - Sistema completo de controle de custos de novilhas
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
    <title>Controle de Novilhas - LacTech</title>
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
            background: linear-gradient(to right, #d946ef, #a855f7);
            color: white;
        }
        .phase-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
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
                <div class="w-10 h-10 bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Controle de Novilhas</h2>
                    <p class="text-sm text-gray-500">Gerencie custos e desenvolvimento de novilhas</p>
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
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4" id="heifer-statistics">
                <div class="bg-gradient-to-br from-fuchsia-50 to-fuchsia-100 rounded-xl p-4 border border-fuchsia-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-fuchsia-700">Total de Novilhas</span>
                        <svg class="w-5 h-5 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-fuchsia-900" id="stat-total">0</p>
                    <p class="text-xs text-fuchsia-600 mt-1">Cadastradas</p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-700">Investimento Total</span>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-900" id="stat-invested">R$ 0,00</p>
                    <p class="text-xs text-blue-600 mt-1">Acumulado</p>
                </div>
                <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 border border-green-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-green-700">Custo Médio</span>
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-green-900" id="stat-avg-cost">R$ 0,00</p>
                    <p class="text-xs text-green-600 mt-1">Por registro</p>
                </div>
                <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 border border-purple-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-purple-700">Em Aleitamento</span>
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-purple-900" id="stat-aleitamento">0</p>
                    <p class="text-xs text-purple-600 mt-1">0-60 dias</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6 flex gap-2 border-b border-gray-200">
                <button onclick="switchTab('heifers')" class="tab-button active px-6 py-3 font-medium text-gray-700 hover:text-gray-900 border-b-2 border-transparent hover:border-fuchsia-600 transition-colors" id="tab-heifers">
                    Novilhas
                </button>
                <button onclick="switchTab('costs')" class="tab-button px-6 py-3 font-medium text-gray-700 hover:text-gray-900 border-b-2 border-transparent hover:border-fuchsia-600 transition-colors" id="tab-costs">
                    Custos
                </button>
                <button onclick="switchTab('analysis')" class="tab-button px-6 py-3 font-medium text-gray-700 hover:text-gray-900 border-b-2 border-transparent hover:border-fuchsia-600 transition-colors" id="tab-analysis">
                    Análises
                </button>
            </div>

            <!-- Tab Content: Novilhas -->
            <div id="content-heifers" class="tab-content">
                <!-- Filtros -->
                <div class="mb-6 flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    <div class="flex flex-wrap gap-3">
                        <input type="text" id="heifers-search" placeholder="Buscar por número, nome..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent min-w-[250px]">
                        <select id="heifers-filter-phase" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                            <option value="">Todas as fases</option>
                            <option value="Aleitamento">Aleitamento</option>
                            <option value="Transição/Desmame">Transição/Desmame</option>
                            <option value="Recria Inicial">Recria Inicial</option>
                            <option value="Recria Intermediária">Recria Intermediária</option>
                            <option value="Crescimento/Desenvolvimento">Crescimento/Desenvolvimento</option>
                            <option value="Pré-parto">Pré-parto</option>
                        </select>
                        <button onclick="loadHeifers()" class="px-4 py-2 bg-fuchsia-600 text-white rounded-lg hover:bg-fuchsia-700 transition-colors font-medium flex items-center">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Buscar
                        </button>
                    </div>
                    <button onclick="openCostForm()" class="px-5 py-2 bg-gradient-to-r from-fuchsia-600 to-fuchsia-700 text-white rounded-lg hover:from-fuchsia-700 hover:to-fuchsia-800 transition-all font-medium shadow-md flex items-center">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Novo Custo
                    </button>
                </div>

                <!-- Lista de Novilhas -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Número</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nome</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Idade</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Fase</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Custo Total</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Registros</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="heifers-list" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                            <p>Carregando novilhas...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Custos -->
            <div id="content-costs" class="tab-content hidden">
                <div class="mb-6 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900">Registros de Custos</h3>
                    <div class="flex gap-3">
                        <input type="date" id="costs-filter-date" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                        <button onclick="loadCosts()" class="px-4 py-2 bg-fuchsia-600 text-white rounded-lg hover:bg-fuchsia-700 transition-colors font-medium">
                            Filtrar
                        </button>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Novilha</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Categoria</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Descrição</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Valor</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                                </tr>
                            </thead>
                            <tbody id="costs-list" class="divide-y divide-gray-200">
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">Carregando custos...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Análises -->
            <div id="content-analysis" class="tab-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Custos por Categoria</h3>
                        <div id="costs-by-category" class="space-y-2">
                            <p class="text-gray-500 text-center">Carregando...</p>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Novilhas Mais Caras</h3>
                        <div id="top-expensive" class="space-y-2">
                            <p class="text-gray-500 text-center">Carregando...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Custo -->
    <div id="cost-form-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" id="cost-form-title">Novo Custo</h3>
                    <button onclick="closeCostForm()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="cost-form" class="p-6 space-y-6">
                    <input type="hidden" id="cost-form-id" name="id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Novilha *</label>
                            <select id="cost-form-animal" name="animal_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data *</label>
                            <input type="date" id="cost-form-date" name="cost_date" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoria *</label>
                            <select id="cost-form-category" name="cost_category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                                <option value="Alimentação">Alimentação</option>
                                <option value="Medicamentos">Medicamentos</option>
                                <option value="Vacinas">Vacinas</option>
                                <option value="Manejo">Manejo</option>
                                <option value="Transporte">Transporte</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Valor (R$) *</label>
                            <input type="number" id="cost-form-amount" name="cost_amount" step="0.01" min="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea id="cost-form-description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-fuchsia-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-fuchsia-600 to-fuchsia-700 text-white rounded-lg hover:from-fuchsia-700 hover:to-fuchsia-800 transition-all font-medium">
                            Salvar Custo
                        </button>
                        <button type="button" onclick="closeCostForm()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div id="heifer-details-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" id="heifer-details-title">Detalhes da Novilha</h3>
                    <button onclick="closeHeiferDetails()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="heifer-details-content" class="p-6">
                    <p class="text-gray-500 text-center">Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/heifer_management.php';
        let currentTab = 'heifers';
        let currentEditId = null;
        let heifersData = [];

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            loadHeifers();
            loadAnimalsForSelect();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Busca em tempo real
            const searchInput = document.getElementById('heifers-search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (currentTab === 'heifers') {
                            loadHeifers();
                        }
                    }, 500);
                });
            }

            // Filtro de fase
            const phaseFilter = document.getElementById('heifers-filter-phase');
            if (phaseFilter) {
                phaseFilter.addEventListener('change', () => {
                    if (currentTab === 'heifers') loadHeifers();
                });
            }
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
                case 'heifers':
                    loadHeifers();
                    break;
                case 'costs':
                    loadCosts();
                    break;
                case 'analysis':
                    loadAnalysis();
                    break;
            }
        }

        // Carregar dashboard
        async function loadDashboard() {
            try {
                const response = await fetch(`${API_BASE}?action=get_dashboard`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const stats = result.data.statistics;
                    document.getElementById('stat-total').textContent = stats.total_heifers || 0;
                    document.getElementById('stat-invested').textContent = formatCurrency(stats.total_invested || 0);
                    document.getElementById('stat-avg-cost').textContent = formatCurrency(stats.avg_cost_per_record || 0);
                    document.getElementById('stat-aleitamento').textContent = stats.phase_aleitamento || 0;
                }
            } catch (error) {
                console.error('Erro ao carregar dashboard:', error);
            }
        }

        // Carregar novilhas
        async function loadHeifers() {
            const tbody = document.getElementById('heifers-list');
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div></td></tr>';
            
            try {
                const response = await fetch(`${API_BASE}?action=get_heifers_list`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.heifers) {
                    heifersData = result.data.heifers;
                    
                    // Aplicar filtros
                    let filtered = heifersData;
                    const search = document.getElementById('heifers-search').value.toLowerCase();
                    const phase = document.getElementById('heifers-filter-phase').value;
                    
                    if (search) {
                        filtered = filtered.filter(h => 
                            (h.ear_tag || '').toLowerCase().includes(search) ||
                            (h.name || '').toLowerCase().includes(search)
                        );
                    }
                    
                    if (phase) {
                        filtered = filtered.filter(h => h.current_phase === phase);
                    }
                    
                    if (filtered.length > 0) {
                        tbody.innerHTML = filtered.map(heifer => `
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">${heifer.ear_tag || 'N/A'}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">${heifer.name || '-'}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${heifer.age_months || 0} meses (${heifer.age_days || 0} dias)</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="phase-badge ${getPhaseColor(heifer.current_phase)}">${heifer.current_phase || '-'}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-center font-medium text-gray-900">${formatCurrency(heifer.total_cost || 0)}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-700">${heifer.total_records || 0}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="viewHeiferDetails(${heifer.id})" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Ver Detalhes">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500"><div class="flex flex-col items-center"><svg class="w-12 h-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg><p>Nenhuma novilha encontrada</p></div></td></tr>';
                    }
                } else {
                    tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Nenhuma novilha encontrada</td></tr>';
                }
            } catch (error) {
                console.error('Erro ao carregar novilhas:', error);
                tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-red-500">Erro ao carregar novilhas</td></tr>';
            }
        }

        // Carregar custos
        async function loadCosts() {
            const tbody = document.getElementById('costs-list');
            tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Carregando...</td></tr>';
            
            try {
                // Por enquanto, vamos buscar os custos através da lista de novilhas
                // Em uma implementação completa, teríamos um endpoint específico
                const response = await fetch(`${API_BASE}?action=get_heifers_list`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.heifers) {
                    // Agregar todos os custos
                    let allCosts = [];
                    
                    for (const heifer of result.data.heifers) {
                        if (heifer.id) {
                            const detailsResponse = await fetch(`${API_BASE}?action=get_heifer_details&animal_id=${heifer.id}`);
                            const detailsResult = await detailsResponse.json();
                            
                            if (detailsResult.success && detailsResult.data && detailsResult.data.recent_costs) {
                                detailsResult.data.recent_costs.forEach(cost => {
                                    allCosts.push({
                                        ...cost,
                                        animal_name: heifer.name || heifer.ear_tag,
                                        animal_number: heifer.ear_tag
                                    });
                                });
                            }
                        }
                    }
                    
                    // Ordenar por data
                    allCosts.sort((a, b) => new Date(b.cost_date) - new Date(a.cost_date));
                    
                    // Limitar a 50 mais recentes
                    allCosts = allCosts.slice(0, 50);
                    
                    if (allCosts.length > 0) {
                        tbody.innerHTML = allCosts.map(cost => `
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900">${formatDate(cost.cost_date)}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${cost.animal_number || '-'} ${cost.animal_name || ''}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${cost.cost_category || '-'}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">${cost.description || '-'}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">${formatCurrency(cost.cost_amount || 0)}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <button onclick="deleteCost(${cost.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Excluir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        `).join('');
                    } else {
                        tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Nenhum custo encontrado</td></tr>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar custos:', error);
                tbody.innerHTML = '<tr><td colspan="6" class="px-4 py-8 text-center text-red-500">Erro ao carregar custos</td></tr>';
            }
        }

        // Carregar análises
        async function loadAnalysis() {
            try {
                const response = await fetch(`${API_BASE}?action=get_dashboard`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    // Custos por categoria
                    const categoryDiv = document.getElementById('costs-by-category');
                    if (result.data.costs_by_category && result.data.costs_by_category.length > 0) {
                        categoryDiv.innerHTML = result.data.costs_by_category.map(cat => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">${cat.category_name || cat.category_type}</p>
                                    <p class="text-xs text-gray-500">${cat.total_records || 0} registros</p>
                                </div>
                                <span class="font-bold text-fuchsia-600">${formatCurrency(cat.total_cost || 0)}</span>
                            </div>
                        `).join('');
                    } else {
                        categoryDiv.innerHTML = '<p class="text-gray-500 text-center">Nenhum dado disponível</p>';
                    }
                    
                    // Top novilhas mais caras
                    const topDiv = document.getElementById('top-expensive');
                    if (result.data.top_expensive_heifers && result.data.top_expensive_heifers.length > 0) {
                        topDiv.innerHTML = result.data.top_expensive_heifers.map((h, index) => `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="text-lg font-bold text-gray-400">#${index + 1}</span>
                                    <div>
                                        <p class="font-medium text-gray-900">${h.ear_tag || 'N/A'}</p>
                                        <p class="text-xs text-gray-500">${h.name || ''} - ${h.age_months || 0} meses</p>
                                    </div>
                                </div>
                                <span class="font-bold text-fuchsia-600">${formatCurrency(h.total_cost || 0)}</span>
                            </div>
                        `).join('');
                    } else {
                        topDiv.innerHTML = '<p class="text-gray-500 text-center">Nenhum dado disponível</p>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar análises:', error);
            }
        }

        // Abrir formulário de custo
        function openCostForm() {
            currentEditId = null;
            document.getElementById('cost-form-title').textContent = 'Novo Custo';
            document.getElementById('cost-form').reset();
            document.getElementById('cost-form-id').value = '';
            document.getElementById('cost-form-date').value = new Date().toISOString().split('T')[0];
            document.getElementById('cost-form-modal').classList.remove('hidden');
        }

        // Fechar formulário
        function closeCostForm() {
            document.getElementById('cost-form-modal').classList.add('hidden');
            currentEditId = null;
        }

        // Carregar animais para select
        async function loadAnimalsForSelect() {
            try {
                const response = await fetch(`${API_BASE}?action=get_heifers_list`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.heifers) {
                    const select = document.getElementById('cost-form-animal');
                    select.innerHTML = '<option value="">Selecione...</option>' + 
                        result.data.heifers.map(h => 
                            `<option value="${h.id}">${h.ear_tag || 'N/A'} - ${h.name || 'Sem nome'}</option>`
                        ).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar animais:', error);
            }
        }

        // Ver detalhes da novilha
        async function viewHeiferDetails(id) {
            try {
                const response = await fetch(`${API_BASE}?action=get_heifer_details&animal_id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const data = result.data;
                    const animal = data.animal;
                    
                    document.getElementById('heifer-details-title').textContent = `Detalhes: ${animal.animal_number || 'N/A'} - ${animal.name || ''}`;
                    
                    const content = document.getElementById('heifer-details-content');
                    content.innerHTML = `
                        <div class="space-y-6">
                            <!-- Informações Básicas -->
                            <div class="border-b border-gray-200 pb-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações Básicas</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Idade</p>
                                        <p class="font-medium text-gray-900">${data.projection.age_months} meses (${data.projection.age_days} dias)</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Fase Atual</p>
                                        <p class="font-medium text-gray-900">${animal.current_phase || '-'}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Custo Total</p>
                                        <p class="font-medium text-fuchsia-600">${formatCurrency(data.total_cost)}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Total de Registros</p>
                                        <p class="font-medium text-gray-900">${data.total_records}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Projeção -->
                            <div class="border-b border-gray-200 pb-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Projeção até 26 Meses</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Custo Médio Diário</p>
                                        <p class="font-medium text-gray-900">${formatCurrency(data.avg_daily_cost)}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Custo Médio Mensal</p>
                                        <p class="font-medium text-gray-900">${formatCurrency(data.avg_monthly_cost)}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Projeção Total</p>
                                        <p class="font-medium text-fuchsia-600">${formatCurrency(data.projection.projected_total_26_months)}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Restante Estimado</p>
                                        <p class="font-medium text-gray-900">${formatCurrency(data.projection.projected_remaining_cost)}</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Custos por Categoria -->
                            <div class="border-b border-gray-200 pb-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Custos por Categoria</h4>
                                <div class="space-y-2">
                                    ${data.costs_by_category.map(cat => `
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <span class="font-medium text-gray-900">${cat.category_name || cat.category_type}</span>
                                            <div class="text-right">
                                                <p class="font-bold text-fuchsia-600">${formatCurrency(cat.total_cost)}</p>
                                                <p class="text-xs text-gray-500">${cat.total_records} registros</p>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            
                            <!-- Últimos Custos -->
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Últimos Registros de Custos</h4>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Data</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Categoria</th>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Descrição</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Valor</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            ${data.recent_costs.slice(0, 10).map(cost => `
                                                <tr>
                                                    <td class="px-3 py-2 text-gray-700">${formatDate(cost.cost_date)}</td>
                                                    <td class="px-3 py-2 text-gray-700">${cost.cost_category}</td>
                                                    <td class="px-3 py-2 text-gray-700">${cost.description || '-'}</td>
                                                    <td class="px-3 py-2 text-right font-medium text-gray-900">${formatCurrency(cost.cost_amount)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('heifer-details-modal').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar detalhes: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao carregar detalhes:', error);
                alert('Erro ao carregar detalhes da novilha');
            }
        }

        // Fechar detalhes
        function closeHeiferDetails() {
            document.getElementById('heifer-details-modal').classList.add('hidden');
        }

        // Deletar custo
        async function deleteCost(id) {
            if (!confirm('Tem certeza que deseja excluir este custo?')) return;
            
            try {
                const response = await fetch(`${API_BASE}?action=delete_cost&id=${id}`, {
                    method: 'GET'
                });
                const result = await response.json();
                
                if (result.success) {
                    loadCosts();
                    loadDashboard();
                    loadHeifers();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Custo excluído com sucesso!');
                    } else {
                        alert('Custo excluído com sucesso!');
                    }
                } else {
                    alert('Erro ao excluir: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao deletar custo:', error);
                alert('Erro ao excluir custo');
            }
        }

        // Submeter formulário de custo
        document.getElementById('cost-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (value !== '') {
                    data[key] = value;
                }
            });
            
            // Converter valores numéricos
            if (data.cost_amount) {
                data.cost_amount = parseFloat(data.cost_amount);
            }
            
            try {
                const response = await fetch(`${API_BASE}?action=add_cost`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeCostForm();
                    loadHeifers();
                    loadCosts();
                    loadDashboard();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Custo registrado com sucesso!');
                    } else {
                        alert('Custo registrado com sucesso!');
                    }
                } else {
                    alert('Erro ao salvar: ' + (result.error || result.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao salvar custo:', error);
                alert('Erro ao salvar custo');
            }
        });

        // Funções auxiliares
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString + 'T00:00:00');
            return date.toLocaleDateString('pt-BR');
        }

        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value || 0);
        }

        function getPhaseColor(phase) {
            const colors = {
                'Aleitamento': 'bg-purple-100 text-purple-800',
                'Transição/Desmame': 'bg-blue-100 text-blue-800',
                'Recria Inicial': 'bg-green-100 text-green-800',
                'Recria Intermediária': 'bg-yellow-100 text-yellow-800',
                'Crescimento/Desenvolvimento': 'bg-orange-100 text-orange-800',
                'Pré-parto': 'bg-red-100 text-red-800'
            };
            return colors[phase] || 'bg-gray-100 text-gray-800';
        }

        // Fechar modais ao clicar fora
        document.getElementById('cost-form-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCostForm();
            }
        });

        document.getElementById('heifer-details-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeHeiferDetails();
            }
        });
    </script>
</body>
</html>
