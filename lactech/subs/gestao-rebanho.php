<?php
/**
 * Página: Gestão de Rebanho
 * Subpágina do Mais Opções - Sistema completo de gestão de animais do rebanho
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
    <title>Gestão de Rebanho - LacTech</title>
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
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19.5 6c-1.3 0-2.5.8-3 2-.5-1.2-1.7-2-3-2s-2.5.8-3 2c-.5-1.2-1.7-2-3-2C5.5 6 4 7.5 4 9.5c0 1.3.7 2.4 1.7 3.1-.4.6-.7 1.3-.7 2.1 0 2.2 1.8 4 4 4h6c2.2 0 4-1.8 4-4 0-.8-.3-1.5-.7-2.1 1-.7 1.7-1.8 1.7-3.1 0-2-1.5-3.5-3.5-3.5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Gestão de Rebanho</h2>
                    <p class="text-sm text-gray-500">Lista completa e gestão dos animais</p>
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
            <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-6 border border-blue-200 fade-in">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Resumo do Rebanho</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-blue-600" id="stat-total">0</p>
                        <p class="text-sm text-gray-600 mt-1">Total de Animais</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-green-600" id="stat-lactating">0</p>
                        <p class="text-sm text-gray-600 mt-1">Lactantes</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-pink-600" id="stat-pregnant">0</p>
                        <p class="text-sm text-gray-600 mt-1">Prenhes</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-orange-600" id="stat-production">0L</p>
                        <p class="text-sm text-gray-600 mt-1">Média Diária</p>
                    </div>
                </div>
            </div>

            <!-- Filtros e Busca -->
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-4 fade-in">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <input type="text" id="search-input" placeholder="Buscar por nome ou número..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <select id="filter-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos os status</option>
                            <option value="Lactante">Lactante</option>
                            <option value="Seco">Seco</option>
                            <option value="Novilha">Novilha</option>
                            <option value="Vaca">Vaca</option>
                            <option value="Bezerra">Bezerra</option>
                            <option value="Bezerro">Bezerro</option>
                            <option value="Touro">Touro</option>
                        </select>
                    </div>
                    <div>
                        <select id="filter-breed" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas as raças</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Lista de Animais -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Animais do Rebanho</h3>
                    <button onclick="openAnimalForm()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        + Adicionar Animal
                    </button>
                </div>
                <div id="animals-list" class="space-y-3">
                    <div class="text-center text-gray-500 py-8">
                        <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <p>Carregando animais...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Detalhes do Animal (Full Screen) -->
    <div id="animal-details-modal" class="fixed inset-0 z-50 hidden bg-white overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="flex-shrink-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between shadow-sm z-10">
            <div class="flex items-center space-x-4">
                <button onclick="closeAnimalDetails()" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div>
                    <h3 class="text-2xl font-bold text-gray-900" id="animal-details-title">Detalhes do Animal</h3>
                    <p class="text-sm text-gray-500" id="animal-details-subtitle">Carregando...</p>
                </div>
            </div>
            <button onclick="closeAnimalDetails()" class="w-10 h-10 flex items-center justify-center hover:bg-gray-100 rounded-lg transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Hero Section com Imagem -->
        <div id="animal-hero-section" class="flex-shrink-0 relative bg-gradient-to-br from-blue-50 to-indigo-50 border-b border-gray-200">
            <div class="container mx-auto px-6 py-6">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <!-- Imagem do Animal -->
                    <div class="flex-shrink-0">
                        <div class="w-48 h-48 md:w-64 md:h-64 rounded-2xl bg-white shadow-xl border-4 border-white overflow-hidden">
                            <img id="animal-details-image" src="../assets/video/vaca.png" alt="Animal" class="w-full h-full object-contain p-4">
                        </div>
                    </div>
                    
                    <!-- Informações Principais -->
                    <div class="flex-1 text-center md:text-left">
                        <h2 id="animal-details-name" class="text-3xl font-bold text-gray-900 mb-2">-</h2>
                        <p id="animal-details-number" class="text-lg text-gray-600 mb-4">#-</p>
                        <div class="flex flex-wrap gap-2 justify-center md:justify-start mb-4">
                            <span id="animal-details-status-badge" class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">-</span>
                            <span id="animal-details-health-badge" class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">-</span>
                            <span id="animal-details-breed-badge" class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">-</span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                            <div class="bg-white rounded-lg p-3 shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Idade</p>
                                <p id="animal-details-age" class="text-lg font-bold text-gray-900">-</p>
                            </div>
                            <div class="bg-white rounded-lg p-3 shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Status Reprodutivo</p>
                                <p id="animal-details-reproductive" class="text-lg font-bold text-gray-900">-</p>
                            </div>
                            <div class="bg-white rounded-lg p-3 shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Grupo</p>
                                <p id="animal-details-group" class="text-lg font-bold text-gray-900">-</p>
                            </div>
                            <div class="bg-white rounded-lg p-3 shadow-sm">
                                <p class="text-xs text-gray-500 mb-1">Último BCS</p>
                                <p id="animal-details-bcs" class="text-lg font-bold text-gray-900">-</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="flex-shrink-0 bg-white border-b border-gray-200 z-10 shadow-sm">
            <div class="container mx-auto px-6">
                <nav class="flex -mb-px overflow-x-auto">
                    <button onclick="switchAnimalTab('info', null, this)" class="animal-tab-button active px-6 py-4 text-sm font-medium text-blue-600 border-b-2 border-blue-600 whitespace-nowrap transition-colors" data-tab="info">
                        Informações
                    </button>
                    <button onclick="switchAnimalTab('health', null, this)" class="animal-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="health">
                        Saúde
                    </button>
                    <button onclick="switchAnimalTab('bcs', null, this)" class="animal-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="bcs">
                        BCS
                    </button>
                    <button onclick="switchAnimalTab('production', null, this)" class="animal-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="production">
                        Produção
                    </button>
                    <button onclick="switchAnimalTab('pedigree', null, this)" class="animal-tab-button px-6 py-4 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent whitespace-nowrap transition-colors" data-tab="pedigree">
                        Pedigree
                    </button>
                </nav>
            </div>
        </div>
        
        <!-- Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="container mx-auto px-6 py-6">
                <div id="animal-details-content" class="max-w-6xl mx-auto">
                    <p class="text-gray-500 text-center py-8">Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Formulário de Animal -->
    <div id="animal-form-modal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900" id="animal-form-title">Novo Animal</h3>
                <button onclick="closeAnimalForm()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="animal-form" class="p-6 space-y-4">
                <input type="hidden" id="animal-id" name="id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Número do Animal *</label>
                        <input type="text" id="animal-number" name="animal_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                        <input type="text" id="animal-name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Raça *</label>
                        <input type="text" id="animal-breed" name="breed" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sexo *</label>
                        <select id="animal-gender" name="gender" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Selecione...</option>
                            <option value="femea">Fêmea</option>
                            <option value="macho">Macho</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data de Nascimento *</label>
                        <input type="date" id="animal-birth-date" name="birth_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Peso ao Nascer (kg)</label>
                        <input type="number" step="0.1" id="animal-birth-weight" name="birth_weight" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="animal-status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="Bezerra">Bezerra</option>
                            <option value="Bezerro">Bezerro</option>
                            <option value="Novilha">Novilha</option>
                            <option value="Lactante">Lactante</option>
                            <option value="Seco">Seco</option>
                            <option value="Vaca">Vaca</option>
                            <option value="Touro">Touro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status de Saúde</label>
                        <select id="animal-health-status" name="health_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="saudavel">Saudável</option>
                            <option value="doente">Doente</option>
                            <option value="tratamento">Em Tratamento</option>
                            <option value="quarentena">Quarentena</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status Reprodutivo</label>
                        <select id="animal-reproductive-status" name="reproductive_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="vazia">Vazia</option>
                            <option value="prenha">Prenha</option>
                            <option value="lactante">Lactante</option>
                            <option value="seca">Seca</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grupo</label>
                        <select id="animal-group" name="current_group_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Sem grupo</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Observações</label>
                    <textarea id="animal-notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Salvar
                    </button>
                    <button type="button" onclick="closeAnimalForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const API_BASE = '../api/herd_management.php';
        let currentAnimalId = null;
        let currentAnimalTab = 'info';
        let breedsList = [];
        let groupsList = [];

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            loadBreeds();
            loadGroups();
            loadAnimals();
            
            // Event listeners para filtros
            document.getElementById('search-input').addEventListener('input', debounce(loadAnimals, 300));
            document.getElementById('filter-status').addEventListener('change', loadAnimals);
            document.getElementById('filter-breed').addEventListener('change', loadAnimals);
        });

        // Dashboard
        async function loadDashboard() {
            try {
                const response = await fetch(`${API_BASE}?action=dashboard`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const stats = result.data;
                    document.getElementById('stat-total').textContent = stats.total_animals || 0;
                    document.getElementById('stat-lactating').textContent = stats.lactating || 0;
                    document.getElementById('stat-pregnant').textContent = stats.pregnant || 0;
                    document.getElementById('stat-production').textContent = (stats.avg_daily_production || 0).toFixed(1) + 'L';
                    
                    // Popular filtro de raças
                    if (stats.breed_distribution) {
                        const breedSelect = document.getElementById('filter-breed');
                        breedSelect.innerHTML = '<option value="">Todas as raças</option>' + 
                            stats.breed_distribution.map(b => `<option value="${b.breed}">${b.breed} (${b.count})</option>`).join('');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar dashboard:', error);
            }
        }

        // Carregar raças e grupos
        async function loadBreeds() {
            try {
                const response = await fetch(`${API_BASE}?action=breeds`);
                const result = await response.json();
                if (result.success) {
                    breedsList = result.data || [];
                }
            } catch (error) {
                console.error('Erro ao carregar raças:', error);
            }
        }

        async function loadGroups() {
            try {
                const response = await fetch(`${API_BASE}?action=groups`);
                const result = await response.json();
                if (result.success) {
                    groupsList = result.data || [];
                    const select = document.getElementById('animal-group');
                    if (select) {
                        select.innerHTML = '<option value="">Sem grupo</option>' + 
                            groupsList.map(g => `<option value="${g.id}">${g.group_name}</option>`).join('');
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar grupos:', error);
            }
        }

        // Listar animais
        async function loadAnimals() {
            const container = document.getElementById('animals-list');
            container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const search = document.getElementById('search-input').value;
                const status = document.getElementById('filter-status').value;
                const breed = document.getElementById('filter-breed').value;
                
                const params = new URLSearchParams({
                    action: 'animals_list',
                    limit: 100,
                    offset: 0
                });
                if (search) params.append('search', search);
                if (status) params.append('status', status);
                if (breed) params.append('breed', breed);
                
                const response = await fetch(`${API_BASE}?${params}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const animals = result.data.animals || [];
                    
                    if (animals.length > 0) {
                        container.innerHTML = animals.map(a => {
                            const ageMonths = a.age_months || 0;
                            const statusColor = getStatusColor(a.status);
                            const healthColor = getHealthColor(a.health_status);
                            
                            return `
                                <div class="p-4 bg-white rounded-lg border border-gray-200 hover:shadow-md transition-shadow fade-in cursor-pointer" onclick="viewAnimalDetails(${a.id})">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <p class="font-medium text-gray-900">${a.animal_number || 'N/A'} - ${a.name || 'Sem nome'}</p>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full ${statusColor}">${a.status || ''}</span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full ${healthColor}">${a.health_status || ''}</span>
                                            </div>
                                            <div class="text-sm text-gray-600 space-y-1">
                                                <p><strong>Raça:</strong> ${a.breed || '-'}</p>
                                                <p><strong>Idade:</strong> ${ageMonths} meses</p>
                                                ${a.group_name ? `<p><strong>Grupo:</strong> ${a.group_name}</p>` : ''}
                                                ${a.reproductive_status ? `<p><strong>Reprodutivo:</strong> ${a.reproductive_status}</p>` : ''}
                                            </div>
                                        </div>
                                        <div class="flex gap-2 ml-4">
                                            <button onclick="event.stopPropagation(); editAnimal(${a.id})" class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                                                Editar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum animal encontrado</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar animais:', error);
                container.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar animais</div>';
            }
        }

        // Ver detalhes do animal
        async function viewAnimalDetails(id) {
            currentAnimalId = id;
            currentAnimalTab = 'info';
            document.getElementById('animal-details-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevenir scroll do body
            loadAnimalDetails();
        }

        function closeAnimalDetails() {
            document.getElementById('animal-details-modal').classList.add('hidden');
            document.body.style.overflow = ''; // Restaurar scroll do body
            currentAnimalId = null;
        }

        async function loadAnimalDetails() {
            if (!currentAnimalId) return;
            
            const content = document.getElementById('animal-details-content');
            content.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando detalhes...</p>';
            
            try {
                const response = await fetch(`${API_BASE}?action=animal_get&id=${currentAnimalId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const animal = result.data;
                    updateAnimalHero(animal);
                    switchAnimalTab(currentAnimalTab, animal);
                }
            } catch (error) {
                console.error('Erro ao carregar detalhes:', error);
                content.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar detalhes</div>';
            }
        }

        function updateAnimalHero(animal) {
            // Atualizar título
            document.getElementById('animal-details-title').textContent = animal.name || animal.animal_number || 'Animal';
            document.getElementById('animal-details-subtitle').textContent = `#${animal.animal_number || 'N/A'}`;
            
            // Atualizar imagem
            const imagePath = getAnimalImage(animal);
            document.getElementById('animal-details-image').src = imagePath;
            
            // Atualizar informações principais
            document.getElementById('animal-details-name').textContent = animal.name || 'Sem nome';
            document.getElementById('animal-details-number').textContent = `#${animal.animal_number || 'N/A'}`;
            
            // Badges
            const statusBadge = document.getElementById('animal-details-status-badge');
            statusBadge.textContent = animal.status || '-';
            statusBadge.className = `px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(animal.status)}`;
            
            const healthBadge = document.getElementById('animal-details-health-badge');
            healthBadge.textContent = animal.health_status || '-';
            healthBadge.className = `px-3 py-1 rounded-full text-sm font-medium ${getHealthColor(animal.health_status)}`;
            
            const breedBadge = document.getElementById('animal-details-breed-badge');
            breedBadge.textContent = animal.breed || '-';
            
            // Estatísticas
            document.getElementById('animal-details-age').textContent = `${animal.age_months || 0} meses`;
            document.getElementById('animal-details-reproductive').textContent = animal.reproductive_status || '-';
            document.getElementById('animal-details-group').textContent = animal.group_name || 'Sem grupo';
            document.getElementById('animal-details-bcs').textContent = animal.latest_bcs ? animal.latest_bcs.score || '-' : '-';
        }

        function getAnimalImage(animal) {
            const status = (animal.status || '').toLowerCase();
            const gender = (animal.gender || '').toLowerCase();
            
            // Verificar se é touro
            if (status.includes('touro') || (gender === 'macho' && status.includes('boi'))) {
                return '../assets/video/touro.png';
            }
            
            // Verificar se é bezerro/bezerra
            if (status.includes('bezerro') || status.includes('bezerra') || status.includes('bezzero') || status.includes('bezzera')) {
                return '../assets/video/bezzero.png';
            }
            
            // Para vacas, novilhas, lactantes, secas - usar imagem de vaca
            return '../assets/video/vaca.png';
        }

        function switchAnimalTab(tab, animal = null, buttonElement = null) {
            currentAnimalTab = tab;
            
            // Atualizar botões - remover active de todos
            document.querySelectorAll('.animal-tab-button').forEach(btn => {
                btn.classList.remove('active', 'text-blue-600', 'border-blue-600');
                btn.classList.add('text-gray-500', 'border-transparent');
            });
            
            // Adicionar active ao botão correto
            const activeButton = buttonElement || document.querySelector(`.animal-tab-button[data-tab="${tab}"]`);
            if (activeButton) {
                activeButton.classList.add('active', 'text-blue-600', 'border-blue-600');
                activeButton.classList.remove('text-gray-500', 'border-transparent');
            }
            
            const content = document.getElementById('animal-details-content');
            
            if (!animal && currentAnimalId) {
                // Recarregar animal se necessário
                fetch(`${API_BASE}?action=animal_get&id=${currentAnimalId}`)
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) switchAnimalTab(tab, result.data);
                    });
                return;
            }
            
            if (!animal) return;
            
            switch(tab) {
                case 'info':
                    content.innerHTML = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Número</p>
                                    <p class="font-medium">${animal.animal_number || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Nome</p>
                                    <p class="font-medium">${animal.name || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Raça</p>
                                    <p class="font-medium">${animal.breed || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Sexo</p>
                                    <p class="font-medium">${animal.gender || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Data de Nascimento</p>
                                    <p class="font-medium">${formatDate(animal.birth_date)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Idade</p>
                                    <p class="font-medium">${animal.age_months || 0} meses</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status</p>
                                    <p class="font-medium">${animal.status || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status de Saúde</p>
                                    <p class="font-medium">${animal.health_status || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status Reprodutivo</p>
                                    <p class="font-medium">${animal.reproductive_status || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Grupo</p>
                                    <p class="font-medium">${animal.group_name || 'Sem grupo'}</p>
                                </div>
                            </div>
                            ${animal.notes ? `<div><p class="text-sm text-gray-600">Observações</p><p class="font-medium">${animal.notes}</p></div>` : ''}
                        </div>
                    `;
                    break;
                    
                case 'health':
                    loadHealthHistory();
                    break;
                    
                case 'bcs':
                    loadBCSHistory();
                    break;
                    
                case 'production':
                    loadProductionHistory();
                    break;
                    
                case 'pedigree':
                    loadPedigree();
                    break;
            }
        }

        async function loadHealthHistory() {
            const content = document.getElementById('animal-details-content');
            content.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando histórico de saúde...</p>';
            
            try {
                const response = await fetch(`${API_BASE}?action=health_history&animal_id=${currentAnimalId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const records = result.data || [];
                    
                    if (records.length > 0) {
                        content.innerHTML = `
                            <div class="space-y-3">
                                ${records.map(r => `
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-start justify-between mb-2">
                                            <div>
                                                <p class="font-medium text-gray-900">${r.record_type || ''}</p>
                                                <p class="text-sm text-gray-600">${r.description || ''}</p>
                                            </div>
                                            <span class="text-xs text-gray-500">${formatDate(r.record_date)}</span>
                                        </div>
                                        ${r.medication ? `<p class="text-sm text-gray-600"><strong>Medicamento:</strong> ${r.medication}</p>` : ''}
                                        ${r.dosage ? `<p class="text-sm text-gray-600"><strong>Dosagem:</strong> ${r.dosage}</p>` : ''}
                                        ${r.veterinarian ? `<p class="text-sm text-gray-600"><strong>Veterinário:</strong> ${r.veterinarian}</p>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum registro de saúde encontrado</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar histórico:', error);
                content.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar histórico</div>';
            }
        }

        async function loadBCSHistory() {
            const content = document.getElementById('animal-details-content');
            content.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando histórico de BCS...</p>';
            
            try {
                const response = await fetch(`${API_BASE}?action=bcs_history&animal_id=${currentAnimalId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const records = result.data || [];
                    
                    if (records.length > 0) {
                        content.innerHTML = `
                            <div class="space-y-3">
                                ${records.map(r => `
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-3">
                                                <span class="text-2xl font-bold ${getBCSColor(r.score)}">${r.score || '-'}</span>
                                                <div>
                                                    <p class="font-medium text-gray-900">BCS</p>
                                                    <p class="text-sm text-gray-600">${formatDate(r.evaluation_date)}</p>
                                                </div>
                                            </div>
                                        </div>
                                        ${r.notes ? `<p class="text-sm text-gray-600">${r.notes}</p>` : ''}
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum registro de BCS encontrado</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar BCS:', error);
                content.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar BCS</div>';
            }
        }

        async function loadProductionHistory() {
            const content = document.getElementById('animal-details-content');
            content.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando histórico de produção...</p>';
            
            try {
                const response = await fetch(`${API_BASE}?action=production_history&animal_id=${currentAnimalId}&limit=30`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const records = result.data || [];
                    
                    if (records.length > 0) {
                        content.innerHTML = `
                            <div class="space-y-3">
                                ${records.map(r => `
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium text-gray-900">${formatDate(r.production_date)}</p>
                                            </div>
                                            <span class="text-lg font-bold text-blue-600">${r.volume || 0}L</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class="text-center text-gray-500 py-8">Nenhum registro de produção encontrado</div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar produção:', error);
                content.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar produção</div>';
            }
        }

        async function loadPedigree() {
            const content = document.getElementById('animal-details-content');
            content.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando pedigree...</p>';
            
            try {
                // Buscar dados do animal atual também
                const animalResponse = await fetch(`${API_BASE}?action=animal_get&id=${currentAnimalId}`);
                const animalResult = await animalResponse.json();
                const currentAnimal = animalResult.success ? animalResult.data : null;
                
                const response = await fetch(`${API_BASE}?action=pedigree&animal_id=${currentAnimalId}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const pedigree = result.data || [];
                    
                    if (pedigree.length > 0 || currentAnimal) {
                        // Organizar pedigree por geração e posição
                        const organized = organizePedigree(pedigree, currentAnimal);
                        content.innerHTML = buildPedigreeTree(organized, currentAnimal);
                    } else {
                        content.innerHTML = `
                            <div class="text-center text-gray-500 py-8">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-lg font-medium mb-2">Nenhum registro de pedigree encontrado</p>
                                <p class="text-sm text-gray-400">Adicione informações do pedigree para visualizar a árvore genealógica</p>
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar pedigree:', error);
                content.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar pedigree</div>';
            }
        }

        function organizePedigree(pedigree, currentAnimal) {
            const organized = {
                generation0: currentAnimal ? {
                    animal: currentAnimal,
                    position: 'animal'
                } : null,
                generation1: {
                    pai: null,
                    mae: null
                },
                generation2: {
                    avo_paterno: null,
                    avo_paterno_mae: null,
                    avo_materno: null,
                    avo_materno_mae: null
                },
                generation3: {
                    avo_paterno_pai: null,
                    avo_paterno_mae: null,
                    avo_materno_pai: null,
                    avo_materno_mae: null
                }
            };
            
            pedigree.forEach(p => {
                if (p.generation === 1) {
                    if (p.position === 'pai') {
                        organized.generation1.pai = p;
                    } else if (p.position === 'mae') {
                        organized.generation1.mae = p;
                    }
                } else if (p.generation === 2) {
                    if (p.position === 'avo_paterno') {
                        organized.generation2.avo_paterno = p;
                    } else if (p.position === 'avo_paterno_mae') {
                        organized.generation2.avo_paterno_mae = p;
                    } else if (p.position === 'avo_materno') {
                        organized.generation2.avo_materno = p;
                    } else if (p.position === 'avo_materno_mae') {
                        organized.generation2.avo_materno_mae = p;
                    }
                } else if (p.generation === 3) {
                    organized.generation3[p.position] = p;
                }
            });
            
            return organized;
        }

        function buildPedigreeTree(organized, currentAnimal) {
            const getPositionLabel = (position) => {
                const labels = {
                    'pai': 'Pai',
                    'mae': 'Mãe',
                    'avo_paterno': 'Avô Paterno',
                    'avo_paterno_mae': 'Avó Paterna',
                    'avo_materno': 'Avô Materno',
                    'avo_materno_mae': 'Avó Materna',
                    'avo_paterno_pai': 'Bisavô Paterno (Pai)',
                    'avo_paterno_mae': 'Bisavó Paterna (Mãe)',
                    'avo_materno_pai': 'Bisavô Materno (Pai)',
                    'avo_materno_mae': 'Bisavó Materna (Mãe)'
                };
                return labels[position] || position;
            };

            const renderAnimalCard = (animal, position, isCurrent = false) => {
                if (!animal) return '<div class="p-3 bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 text-center text-gray-400 text-sm">Não informado</div>';
                
                const name = animal.related_animal_name || animal.animal_name || animal.name || 'Sem nome';
                const number = animal.related_animal_number || animal.animal_number || '-';
                const breed = animal.related_animal_breed || animal.breed || '-';
                const bgColor = isCurrent ? 'bg-blue-50 border-blue-300' : 'bg-white border-gray-200';
                const textColor = isCurrent ? 'text-blue-900' : 'text-gray-900';
                
                return `
                    <div class="p-4 ${bgColor} rounded-lg border-2 ${isCurrent ? 'border-blue-400' : 'border-gray-300'} hover:shadow-md transition-all">
                        ${position && !isCurrent ? `<p class="text-xs font-semibold text-gray-500 mb-2 uppercase">${getPositionLabel(position)}</p>` : ''}
                        ${isCurrent ? `<p class="text-xs font-semibold text-blue-600 mb-2 uppercase">Animal Atual</p>` : ''}
                        <p class="font-bold ${textColor} text-lg mb-1">${name}</p>
                        <p class="text-sm text-gray-600 mb-1">#${number}</p>
                        <p class="text-xs text-gray-500">${breed}</p>
                        ${animal.notes ? `<p class="text-xs text-gray-400 mt-2 italic">${animal.notes}</p>` : ''}
                    </div>
                `;
            };

            return `
                <div class="space-y-6">
                    <!-- Geração 0 - Animal Atual -->
                    ${organized.generation0 ? `
                        <div class="text-center">
                            <h4 class="text-sm font-semibold text-gray-500 mb-3 uppercase">Animal</h4>
                            ${renderAnimalCard(organized.generation0.animal, null, true)}
                        </div>
                    ` : ''}
                    
                    <!-- Geração 1 - Pais -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-500 mb-3 uppercase text-center">Geração 1 - Pais</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                ${renderAnimalCard(organized.generation1.pai, 'pai')}
                            </div>
                            <div>
                                ${renderAnimalCard(organized.generation1.mae, 'mae')}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Geração 2 - Avós -->
                    ${(organized.generation2.avo_paterno || organized.generation2.avo_paterno_mae || organized.generation2.avo_materno || organized.generation2.avo_materno_mae) ? `
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 mb-3 uppercase text-center">Geração 2 - Avós</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-400 mb-2">Lado Paterno</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            ${renderAnimalCard(organized.generation2.avo_paterno, 'avo_paterno')}
                                        </div>
                                        <div>
                                            ${renderAnimalCard(organized.generation2.avo_paterno_mae, 'avo_paterno_mae')}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-400 mb-2">Lado Materno</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            ${renderAnimalCard(organized.generation2.avo_materno, 'avo_materno')}
                                        </div>
                                        <div>
                                            ${renderAnimalCard(organized.generation2.avo_materno_mae, 'avo_materno_mae')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Geração 3 - Bisavós -->
                    ${(organized.generation3.avo_paterno_pai || organized.generation3.avo_paterno_mae || organized.generation3.avo_materno_pai || organized.generation3.avo_materno_mae) ? `
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500 mb-3 uppercase text-center">Geração 3 - Bisavós</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-400 mb-2">Bisavós Paternos</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            ${renderAnimalCard(organized.generation3.avo_paterno_pai, 'avo_paterno_pai')}
                                        </div>
                                        <div>
                                            ${renderAnimalCard(organized.generation3.avo_paterno_mae, 'avo_paterno_mae')}
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-400 mb-2">Bisavós Maternos</p>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            ${renderAnimalCard(organized.generation3.avo_materno_pai, 'avo_materno_pai')}
                                        </div>
                                        <div>
                                            ${renderAnimalCard(organized.generation3.avo_materno_mae, 'avo_materno_mae')}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Botão para adicionar/editar pedigree -->
                    <div class="pt-4 border-t border-gray-200">
                        <button onclick="openPedigreeForm()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                            ${organized.generation0 && (organized.generation1.pai || organized.generation1.mae) ? 'Editar Pedigree' : 'Adicionar Pedigree'}
                        </button>
                    </div>
                </div>
            `;
        }

        // Formulário de animal
        function openAnimalForm(id = null) {
            document.getElementById('animal-form-modal').classList.remove('hidden');
            document.getElementById('animal-form').reset();
            document.getElementById('animal-id').value = id || '';
            document.getElementById('animal-form-title').textContent = id ? 'Editar Animal' : 'Novo Animal';
            
            if (id) {
                loadAnimalForEdit(id);
            }
        }

        async function loadAnimalForEdit(id) {
            try {
                const response = await fetch(`${API_BASE}?action=animal_get&id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const a = result.data;
                    document.getElementById('animal-number').value = a.animal_number || '';
                    document.getElementById('animal-name').value = a.name || '';
                    document.getElementById('animal-breed').value = a.breed || '';
                    document.getElementById('animal-gender').value = a.gender || '';
                    document.getElementById('animal-birth-date').value = a.birth_date || '';
                    document.getElementById('animal-birth-weight').value = a.birth_weight || '';
                    document.getElementById('animal-status').value = a.status || 'Bezerra';
                    document.getElementById('animal-health-status').value = a.health_status || 'saudavel';
                    document.getElementById('animal-reproductive-status').value = a.reproductive_status || 'vazia';
                    document.getElementById('animal-group').value = a.current_group_id || '';
                    document.getElementById('animal-notes').value = a.notes || '';
                }
            } catch (error) {
                console.error('Erro ao carregar animal:', error);
            }
        }

        function closeAnimalForm() {
            document.getElementById('animal-form-modal').classList.add('hidden');
        }

        function editAnimal(id) {
            openAnimalForm(id);
        }

        document.getElementById('animal-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            const id = data.id;
            
            try {
                const url = id ? 
                    `${API_BASE}?action=animal_update` : 
                    `${API_BASE}?action=animal_create`;
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast(result.data?.message || 'Animal salvo com sucesso!');
                    }
                    closeAnimalForm();
                    loadAnimals();
                    loadDashboard();
                } else {
                    if (typeof window.showErrorToast === 'function') {
                        window.showErrorToast(result.error || 'Erro ao salvar animal');
                    }
                }
            } catch (error) {
                console.error('Erro ao salvar animal:', error);
                if (typeof window.showErrorToast === 'function') {
                    window.showErrorToast('Erro ao salvar animal');
                }
            }
        });

        // Funções auxiliares
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function getStatusColor(status) {
            const colors = {
                'Lactante': 'bg-green-100 text-green-800',
                'Seco': 'bg-yellow-100 text-yellow-800',
                'Novilha': 'bg-blue-100 text-blue-800',
                'Bezerra': 'bg-pink-100 text-pink-800',
                'Bezerro': 'bg-purple-100 text-purple-800',
                'Touro': 'bg-red-100 text-red-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        function getHealthColor(status) {
            const colors = {
                'saudavel': 'bg-green-100 text-green-800',
                'doente': 'bg-red-100 text-red-800',
                'tratamento': 'bg-yellow-100 text-yellow-800',
                'quarentena': 'bg-orange-100 text-orange-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        }

        function getBCSColor(score) {
            if (score < 2.5) return 'text-red-600';
            if (score < 3.5) return 'text-yellow-600';
            return 'text-green-600';
        }

        function openPedigreeForm() {
            if (!currentAnimalId) return;
            
            // Por enquanto, apenas mostrar mensagem
            // Em uma implementação completa, abriria um modal para editar pedigree
            if (typeof window.showInfoToast === 'function') {
                window.showInfoToast('Funcionalidade de edição de pedigree será implementada em breve');
            } else {
                alert('Funcionalidade de edição de pedigree será implementada em breve');
            }
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
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
