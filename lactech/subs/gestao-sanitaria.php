<?php
/**
 * Página: Gestão Sanitária
 * Sistema completo de gestão sanitária do rebanho
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
        <div class="bg-white border-b border-gray-200 sticky top-0 z-20 shadow-sm">
            <div class="container mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <button onclick="window.parent.postMessage({type: 'closeModal'}, '*')" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Gestão Sanitária</h1>
                                <p class="text-sm text-gray-600">Controle de saúde e bem-estar do rebanho</p>
                            </div>
                        </div>
                    </div>
                    <button onclick="openHealthForm()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span>Novo Registro</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Dashboard -->
        <div class="container mx-auto px-6 py-6">
            <div id="dashboard-section" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Total de Registros</p>
                                <p id="stat-total-records" class="text-3xl font-bold text-gray-900">-</p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Vacinações Pendentes</p>
                                <p id="stat-pending-vaccinations" class="text-3xl font-bold text-orange-600">-</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Vermifugações Pendentes</p>
                                <p id="stat-pending-dewormings" class="text-3xl font-bold text-purple-600">-</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 mb-1">Alertas Ativos</p>
                                <p id="stat-active-alerts" class="text-3xl font-bold text-red-600">-</p>
                            </div>
                            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px overflow-x-auto">
                        <button onclick="switchTab('all', this)" class="health-tab-button active px-6 py-4 text-sm font-medium text-green-600 border-b-2 border-green-600 whitespace-nowrap transition-colors" data-tab="all">
                            Todos os Registros
                        </button>
                        <button onclick="switchTab('vacination', this)" class="health-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="vacination">
                            Vacinações
                        </button>
                        <button onclick="switchTab('medication', this)" class="health-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="medication">
                            Medicamentos
                        </button>
                        <button onclick="switchTab('deworming', this)" class="health-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="deworming">
                            Vermifugação
                        </button>
                        <button onclick="switchTab('alerts', this)" class="health-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="alerts">
                            Alertas
                        </button>
                    </nav>
                </div>

                <!-- Filters -->
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Animal</label>
                            <select id="filter-animal" onchange="loadRecords()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                <option value="">Todos os animais</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Inicial</label>
                            <input type="date" id="filter-date-from" onchange="loadRecords()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data Final</label>
                            <input type="date" id="filter-date-to" onchange="loadRecords()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div class="flex items-end">
                            <button onclick="clearFilters()" class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div id="records-content" class="p-6">
                    <p class="text-gray-500 text-center py-8">Carregando registros...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Formulário de Registro Sanitário -->
    <div id="health-form-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900" id="form-modal-title">Novo Registro Sanitário</h3>
                <button onclick="closeHealthForm()" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="health-form" onsubmit="saveHealthRecord(event)" class="p-6 space-y-4">
                <input type="hidden" id="record-id" name="id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Animal *</label>
                    <select id="form-animal-id" name="animal_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Selecione um animal</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data do Registro *</label>
                        <input type="date" id="form-record-date" name="record_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Registro *</label>
                        <select id="form-record-type" name="record_type" required onchange="toggleFormFields()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="">Selecione</option>
                            <option value="Vacinação">Vacinação</option>
                            <option value="Medicamento">Medicamento</option>
                            <option value="Vermifugação">Vermifugação</option>
                            <option value="Suplementação">Suplementação</option>
                            <option value="Cirurgia">Cirurgia</option>
                            <option value="Consulta">Consulta</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrição *</label>
                    <textarea id="form-description" name="description" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Medicamento/Vacina</label>
                        <input type="text" id="form-medication" name="medication" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Dosagem</label>
                        <input type="text" id="form-dosage" name="dosage" placeholder="Ex: 2ml, 5mg" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custo (R$)</label>
                        <input type="number" id="form-cost" name="cost" step="0.01" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Próxima Data</label>
                        <input type="date" id="form-next-date" name="next_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Veterinário</label>
                    <input type="text" id="form-veterinarian" name="veterinarian" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeHealthForm()" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-[99999]"></div>

    <script src="../assets/js/toast-notifications.js?v=<?php echo $v; ?>"></script>
    <script>
        const API_BASE = '../api/health_management.php';
        let currentTab = 'all';
        let currentRecordId = null;

        // Carregar dados iniciais
        document.addEventListener('DOMContentLoaded', () => {
            loadDashboard();
            loadAnimals();
            loadRecords();
            setDefaultDates();
        });

        function setDefaultDates() {
            const today = new Date();
            const lastMonth = new Date();
            lastMonth.setMonth(lastMonth.getMonth() - 1);
            
            document.getElementById('filter-date-from').value = lastMonth.toISOString().split('T')[0];
            document.getElementById('filter-date-to').value = today.toISOString().split('T')[0];
        }

        async function loadDashboard() {
            try {
                const response = await fetch(`${API_BASE}?action=dashboard`);
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('stat-total-records').textContent = result.data.total_records || 0;
                    document.getElementById('stat-pending-vaccinations').textContent = result.data.pending_vaccinations || 0;
                    document.getElementById('stat-pending-dewormings').textContent = result.data.pending_dewormings || 0;
                    document.getElementById('stat-active-alerts').textContent = result.data.active_alerts || 0;
                }
            } catch (error) {
                console.error('Erro ao carregar dashboard:', error);
            }
        }

        async function loadAnimals() {
            try {
                const response = await fetch(`${API_BASE}?action=animals`);
                const result = await response.json();
                
                if (result.success) {
                    const select = document.getElementById('form-animal-id');
                    const filterSelect = document.getElementById('filter-animal');
                    
                    select.innerHTML = '<option value="">Selecione um animal</option>';
                    filterSelect.innerHTML = '<option value="">Todos os animais</option>';
                    
                    result.data.forEach(animal => {
                        const option1 = document.createElement('option');
                        option1.value = animal.id;
                        option1.textContent = `${animal.animal_number} - ${animal.name || 'Sem nome'}`;
                        select.appendChild(option1);
                        
                        const option2 = document.createElement('option');
                        option2.value = animal.id;
                        option2.textContent = `${animal.animal_number} - ${animal.name || 'Sem nome'}`;
                        filterSelect.appendChild(option2);
                    });
                }
            } catch (error) {
                console.error('Erro ao carregar animais:', error);
            }
        }

        async function loadRecords() {
            const content = document.getElementById('records-content');
            content.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando registros...</p>';
            
            try {
                const params = new URLSearchParams();
                params.append('action', 'list');
                
                if (currentTab !== 'all') {
                    const typeMap = {
                        'vacination': 'Vacinação',
                        'medication': 'Medicamento',
                        'deworming': 'Vermifugação'
                    };
                    if (typeMap[currentTab]) {
                        params.append('record_type', typeMap[currentTab]);
                    }
                }
                
                const animalId = document.getElementById('filter-animal').value;
                if (animalId) params.append('animal_id', animalId);
                
                const dateFrom = document.getElementById('filter-date-from').value;
                if (dateFrom) params.append('date_from', dateFrom);
                
                const dateTo = document.getElementById('filter-date-to').value;
                if (dateTo) params.append('date_to', dateTo);
                
                if (currentTab === 'alerts') {
                    await loadAlerts();
                    return;
                }
                
                const response = await fetch(`${API_BASE}?${params.toString()}`);
                const result = await response.json();
                
                if (result.success) {
                    if (result.data.records.length === 0) {
                        content.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum registro encontrado</p>';
                        return;
                    }
                    
                    content.innerHTML = `
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicamento</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Próxima Data</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${result.data.records.map(record => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${formatDate(record.record_date)}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${record.animal_number || '-'} ${record.animal_name ? `- ${record.animal_name}` : ''}</td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full ${getRecordTypeColor(record.record_type)}">
                                                    ${record.record_type}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-900">${record.description || '-'}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${record.medication || '-'}</td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm ${record.next_date && new Date(record.next_date) <= new Date() ? 'text-red-600 font-medium' : 'text-gray-900'}">
                                                ${record.next_date ? formatDate(record.next_date) : '-'}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                                <button onclick="editRecord(${record.id})" class="text-blue-600 hover:text-blue-800 mr-3">Editar</button>
                                                <button onclick="deleteRecord(${record.id})" class="text-red-600 hover:text-red-800">Excluir</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    content.innerHTML = `<p class="text-red-500 text-center py-8">${result.message || 'Erro ao carregar registros'}</p>`;
                }
            } catch (error) {
                content.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar registros</p>';
                console.error('Erro:', error);
            }
        }

        async function loadAlerts() {
            const content = document.getElementById('records-content');
            
            try {
                const response = await fetch(`${API_BASE}?action=alerts&resolved=0`);
                const result = await response.json();
                
                if (result.success) {
                    if (result.data.length === 0) {
                        content.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum alerta ativo</p>';
                        return;
                    }
                    
                    content.innerHTML = `
                        <div class="space-y-4">
                            ${result.data.map(alert => `
                                <div class="p-4 bg-${getAlertTypeColor(alert.alert_type)}-50 border-l-4 border-${getAlertTypeColor(alert.alert_type)}-500 rounded-lg">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded bg-${getAlertTypeColor(alert.alert_type)}-200 text-${getAlertTypeColor(alert.alert_type)}-800">
                                                    ${alert.alert_type}
                                                </span>
                                                <span class="text-sm text-gray-600">${formatDate(alert.alert_date)}</span>
                                            </div>
                                            <p class="font-medium text-gray-900 mb-1">${alert.animal_number || '-'} ${alert.animal_name ? `- ${alert.animal_name}` : ''}</p>
                                            <p class="text-sm text-gray-700">${alert.alert_message}</p>
                                        </div>
                                        <button onclick="resolveAlert(${alert.id})" class="ml-4 px-3 py-1 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                            Resolver
                                        </button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                }
            } catch (error) {
                content.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar alertas</p>';
                console.error('Erro:', error);
            }
        }

        function switchTab(tab, buttonElement) {
            currentTab = tab;
            
            // Atualizar botões
            document.querySelectorAll('.health-tab-button').forEach(btn => {
                btn.classList.remove('active', 'text-green-600', 'border-green-600');
                btn.classList.add('text-gray-500', 'border-transparent');
            });
            
            if (buttonElement) {
                buttonElement.classList.add('active', 'text-green-600', 'border-green-600');
                buttonElement.classList.remove('text-gray-500', 'border-transparent');
            }
            
            loadRecords();
        }

        function openHealthForm(recordId = null) {
            currentRecordId = recordId;
            const modal = document.getElementById('health-form-modal');
            const form = document.getElementById('health-form');
            const title = document.getElementById('form-modal-title');
            
            if (recordId) {
                title.textContent = 'Editar Registro Sanitário';
                loadRecordForEdit(recordId);
            } else {
                title.textContent = 'Novo Registro Sanitário';
                form.reset();
                document.getElementById('record-id').value = '';
            }
            
            modal.classList.remove('hidden');
        }

        function closeHealthForm() {
            document.getElementById('health-form-modal').classList.add('hidden');
            document.getElementById('health-form').reset();
            currentRecordId = null;
        }

        async function loadRecordForEdit(id) {
            try {
                const response = await fetch(`${API_BASE}?action=get&id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const record = result.data;
                    document.getElementById('record-id').value = record.id;
                    document.getElementById('form-animal-id').value = record.animal_id;
                    document.getElementById('form-record-date').value = record.record_date;
                    document.getElementById('form-record-type').value = record.record_type;
                    document.getElementById('form-description').value = record.description || '';
                    document.getElementById('form-medication').value = record.medication || '';
                    document.getElementById('form-dosage').value = record.dosage || '';
                    document.getElementById('form-cost').value = record.cost || '';
                    document.getElementById('form-next-date').value = record.next_date || '';
                    document.getElementById('form-veterinarian').value = record.veterinarian || '';
                }
            } catch (error) {
                console.error('Erro ao carregar registro:', error);
                showErrorToast('Erro ao carregar registro');
            }
        }

        async function saveHealthRecord(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                action: document.getElementById('record-id').value ? 'update' : 'create',
                id: document.getElementById('record-id').value || null,
                animal_id: formData.get('animal_id'),
                record_date: formData.get('record_date'),
                record_type: formData.get('record_type'),
                description: formData.get('description'),
                medication: formData.get('medication'),
                dosage: formData.get('dosage'),
                cost: formData.get('cost'),
                next_date: formData.get('next_date'),
                veterinarian: formData.get('veterinarian')
            };
            
            if (data.id) {
                data.id = parseInt(data.id);
            }
            
            try {
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessToast(result.message || 'Registro salvo com sucesso');
                    closeHealthForm();
                    loadDashboard();
                    loadRecords();
                } else {
                    showErrorToast(result.message || 'Erro ao salvar registro');
                }
            } catch (error) {
                console.error('Erro:', error);
                showErrorToast('Erro ao salvar registro');
            }
        }

        function editRecord(id) {
            openHealthForm(id);
        }

        async function deleteRecord(id) {
            if (!confirm('Tem certeza que deseja excluir este registro?')) {
                return;
            }
            
            try {
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessToast('Registro excluído com sucesso');
                    loadDashboard();
                    loadRecords();
                } else {
                    showErrorToast(result.message || 'Erro ao excluir registro');
                }
            } catch (error) {
                console.error('Erro:', error);
                showErrorToast('Erro ao excluir registro');
            }
        }

        async function resolveAlert(id) {
            try {
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'resolve_alert',
                        id: id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessToast('Alerta resolvido com sucesso');
                    loadDashboard();
                    loadAlerts();
                } else {
                    showErrorToast(result.message || 'Erro ao resolver alerta');
                }
            } catch (error) {
                console.error('Erro:', error);
                showErrorToast('Erro ao resolver alerta');
            }
        }

        function clearFilters() {
            document.getElementById('filter-animal').value = '';
            document.getElementById('filter-date-from').value = '';
            document.getElementById('filter-date-to').value = '';
            setDefaultDates();
            loadRecords();
        }

        function toggleFormFields() {
            // Pode adicionar lógica para mostrar/ocultar campos baseado no tipo
        }

        function getRecordTypeColor(type) {
            const colors = {
                'Vacinação': 'bg-blue-100 text-blue-800',
                'Medicamento': 'bg-red-100 text-red-800',
                'Vermifugação': 'bg-purple-100 text-purple-800',
                'Suplementação': 'bg-yellow-100 text-yellow-800',
                'Cirurgia': 'bg-orange-100 text-orange-800',
                'Consulta': 'bg-green-100 text-green-800',
                'Outros': 'bg-gray-100 text-gray-800'
            };
            return colors[type] || 'bg-gray-100 text-gray-800';
        }

        function getAlertTypeColor(type) {
            const colors = {
                'vacina': 'blue',
                'vermifugo': 'purple',
                'medicamento': 'red',
                'consulta': 'green',
                'parto': 'yellow',
                'outros': 'gray'
            };
            return colors[type] || 'gray';
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }
    </script>
</body>
</html>
