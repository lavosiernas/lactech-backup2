<?php
/**
 * Página: Grupos e Lotes
 * Subpágina do Mais Opções - Sistema completo de gestão de grupos e lotes
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
    <title>Grupos e Lotes - LacTech</title>
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
                <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Grupos e Lotes</h2>
                    <p class="text-sm text-gray-500">Organize e gerencie grupos de animais</p>
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
            <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-violet-50 to-violet-100 rounded-xl p-4 border border-violet-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-violet-700">Total de Grupos</span>
                        <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-violet-900" id="stat-total">0</p>
                    <p class="text-xs text-violet-600 mt-1">Ativos</p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-700">Animais Agrupados</span>
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-blue-900" id="stat-grouped">0</p>
                    <p class="text-xs text-blue-600 mt-1">Em grupos</p>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 border border-gray-200 fade-in">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700">Sem Grupo</span>
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <p class="text-2xl font-bold text-gray-900" id="stat-ungrouped">0</p>
                    <p class="text-xs text-gray-600 mt-1">Sem grupo atribuído</p>
                </div>
            </div>

            <!-- Ações -->
            <div class="mb-6 flex flex-wrap gap-3">
                <button onclick="openGroupForm()" class="px-5 py-2 bg-gradient-to-r from-violet-600 to-violet-700 text-white rounded-lg hover:from-violet-700 hover:to-violet-800 transition-all font-medium shadow-md flex items-center">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Novo Grupo
                </button>
                <button onclick="loadGroups()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium flex items-center">
                    <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Atualizar
                </button>
            </div>

            <!-- Lista de Grupos -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="groups-list">
                <div class="col-span-full text-center text-gray-500 py-8">
                    <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <p>Carregando grupos...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Grupo -->
    <div id="group-form-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" id="group-form-title">Novo Grupo</h3>
                    <button onclick="closeGroupForm()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="group-form" class="p-6 space-y-6">
                    <input type="hidden" id="group-form-id" name="id">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Grupo *</label>
                            <input type="text" id="group-form-name" name="group_name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Código</label>
                            <input type="text" id="group-form-code" name="group_code" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                            <select id="group-form-type" name="group_type" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                                <option value="lactante">Lactante</option>
                                <option value="seco">Seco</option>
                                <option value="novilha">Novilha</option>
                                <option value="pre_parto">Pré-parto</option>
                                <option value="pos_parto">Pós-parto</option>
                                <option value="hospital">Hospital</option>
                                <option value="quarentena">Quarentena</option>
                                <option value="pasto">Pasto</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Capacidade</label>
                            <input type="number" id="group-form-capacity" name="capacity" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Localização</label>
                            <input type="text" id="group-form-location" name="location" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ordem de Ordenha</label>
                            <input type="number" id="group-form-milking-order" name="milking_order" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
                            <input type="color" id="group-form-color" name="color_code" value="#6B7280" class="w-full h-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
                        <textarea id="group-form-description" name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Protocolo de Alimentação</label>
                        <textarea id="group-form-feed-protocol" name="feed_protocol" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-violet-600 to-violet-700 text-white rounded-lg hover:from-violet-700 hover:to-violet-800 transition-all font-medium">
                            Salvar Grupo
                        </button>
                        <button type="button" onclick="closeGroupForm()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Grupo -->
    <div id="group-details-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" id="group-details-title">Detalhes do Grupo</h3>
                    <button onclick="closeGroupDetails()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="group-details-content" class="p-6">
                    <p class="text-gray-500 text-center">Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Mover Animais -->
    <div id="move-animals-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">Mover Animais</h3>
                    <button onclick="closeMoveAnimalsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Selecionar Grupo de Destino</label>
                        <select id="move-target-group" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent">
                            <option value="">Selecione um grupo...</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Animais Selecionados</label>
                        <div id="move-animals-list" class="border border-gray-300 rounded-lg p-4 max-h-64 overflow-y-auto">
                            <p class="text-gray-500 text-center">Nenhum animal selecionado</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-gray-200">
                        <button onclick="confirmMoveAnimals()" class="flex-1 px-6 py-3 bg-gradient-to-r from-violet-600 to-violet-700 text-white rounded-lg hover:from-violet-700 hover:to-violet-800 transition-all font-medium">
                            Mover Animais
                        </button>
                        <button onclick="closeMoveAnimalsModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/groups.php';
        let currentEditId = null;
        let selectedAnimals = [];
        let currentGroupId = null;

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadGroups();
        });

        // Carregar grupos
        async function loadGroups() {
            const container = document.getElementById('groups-list');
            container.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const response = await fetch(`${API_BASE}?action=list`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const groups = result.data;
                    
                    // Atualizar estatísticas
                    let totalGrouped = 0;
                    groups.forEach(g => {
                        totalGrouped += parseInt(g.current_count || 0);
                    });
                    
                    document.getElementById('stat-total').textContent = groups.length;
                    document.getElementById('stat-grouped').textContent = totalGrouped;
                    
                    // Buscar animais sem grupo
                    const ungroupedResponse = await fetch(`${API_BASE}?action=animals_without_group`);
                    const ungroupedResult = await ungroupedResponse.json();
                    if (ungroupedResult.success) {
                        document.getElementById('stat-ungrouped').textContent = ungroupedResult.data.length || 0;
                    }
                    
                    if (groups.length > 0) {
                        container.innerHTML = groups.map(group => `
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-shadow fade-in">
                                <div class="p-4 border-b border-gray-200" style="border-left: 4px solid ${group.color_code || '#6B7280'}">
                                    <div class="flex items-center justify-between mb-2">
                                        <h3 class="text-lg font-bold text-gray-900">${group.group_name || 'Sem nome'}</h3>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full ${getTypeColor(group.group_type)}">${formatType(group.group_type)}</span>
                                    </div>
                                    ${group.group_code ? `<p class="text-sm text-gray-600 mb-2">Código: ${group.group_code}</p>` : ''}
                                    ${group.location ? `<p class="text-xs text-gray-500 mb-1">📍 ${group.location}</p>` : ''}
                                </div>
                                <div class="p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <p class="text-2xl font-bold text-gray-900">${group.current_count || 0}</p>
                                            <p class="text-xs text-gray-500">animais</p>
                                        </div>
                                        ${group.capacity ? `
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-700">Capacidade</p>
                                                <p class="text-lg font-bold ${parseInt(group.current_count || 0) >= parseInt(group.capacity) ? 'text-red-600' : 'text-gray-900'}">${group.capacity}</p>
                                            </div>
                                        ` : ''}
                                    </div>
                                    <div class="flex gap-2">
                                        <button onclick="viewGroupDetails(${group.id})" class="flex-1 px-3 py-2 bg-violet-100 text-violet-700 rounded-lg hover:bg-violet-200 transition-colors text-sm font-medium">
                                            Ver Detalhes
                                        </button>
                                        <button onclick="editGroup(${group.id})" class="px-3 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-medium">
                                            Editar
                                        </button>
                                        <button onclick="deleteGroup(${group.id})" class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors text-sm font-medium">
                                            Excluir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg><p>Nenhum grupo encontrado</p><p class="text-sm mt-2">Clique em "Novo Grupo" para criar um</p></div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar grupos:', error);
                container.innerHTML = '<div class="col-span-full text-center text-red-500 py-8">Erro ao carregar grupos</div>';
            }
        }

        // Abrir formulário
        function openGroupForm() {
            currentEditId = null;
            document.getElementById('group-form-title').textContent = 'Novo Grupo';
            document.getElementById('group-form').reset();
            document.getElementById('group-form-id').value = '';
            document.getElementById('group-form-color').value = '#6B7280';
            document.getElementById('group-form-modal').classList.remove('hidden');
        }

        // Fechar formulário
        function closeGroupForm() {
            document.getElementById('group-form-modal').classList.add('hidden');
            currentEditId = null;
        }

        // Editar grupo
        async function editGroup(id) {
            try {
                const response = await fetch(`${API_BASE}?action=get&id=${id}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const group = result.data;
                    currentEditId = id;
                    
                    document.getElementById('group-form-title').textContent = 'Editar Grupo';
                    document.getElementById('group-form-id').value = group.id;
                    document.getElementById('group-form-name').value = group.group_name || '';
                    document.getElementById('group-form-code').value = group.group_code || '';
                    document.getElementById('group-form-type').value = group.group_type || 'lactante';
                    document.getElementById('group-form-capacity').value = group.capacity || '';
                    document.getElementById('group-form-location').value = group.location || '';
                    document.getElementById('group-form-milking-order').value = group.milking_order || '';
                    document.getElementById('group-form-color').value = group.color_code || '#6B7280';
                    document.getElementById('group-form-description').value = group.description || '';
                    document.getElementById('group-form-feed-protocol').value = group.feed_protocol || '';
                    
                    document.getElementById('group-form-modal').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar grupo: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao editar grupo:', error);
                alert('Erro ao carregar grupo');
            }
        }

        // Deletar grupo
        async function deleteGroup(id) {
            if (!confirm('Tem certeza que deseja excluir este grupo?')) return;
            
            try {
                const response = await fetch(`${API_BASE}?action=delete&id=${id}`, {
                    method: 'GET'
                });
                const result = await response.json();
                
                if (result.success) {
                    loadGroups();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Grupo excluído com sucesso!');
                    } else {
                        alert('Grupo excluído com sucesso!');
                    }
                } else {
                    alert('Erro ao excluir: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao deletar grupo:', error);
                alert('Erro ao excluir grupo');
            }
        }

        // Ver detalhes do grupo
        async function viewGroupDetails(id) {
            try {
                const [groupResponse, animalsResponse] = await Promise.all([
                    fetch(`${API_BASE}?action=get&id=${id}`),
                    fetch(`${API_BASE}?action=animals&group_id=${id}`)
                ]);
                
                const groupResult = await groupResponse.json();
                const animalsResult = await animalsResponse.json();
                
                if (groupResult.success && groupResult.data) {
                    const group = groupResult.data;
                    const animals = animalsResult.success ? animalsResult.data : [];
                    
                    document.getElementById('group-details-title').textContent = group.group_name;
                    
                    const content = document.getElementById('group-details-content');
                    content.innerHTML = `
                        <div class="space-y-6">
                            <!-- Informações do Grupo -->
                            <div class="border-b border-gray-200 pb-4">
                                <h4 class="text-lg font-semibold text-gray-900 mb-4">Informações do Grupo</h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500">Código</p>
                                        <p class="font-medium text-gray-900">${group.group_code || '-'}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Tipo</p>
                                        <p class="font-medium text-gray-900">${formatType(group.group_type)}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Localização</p>
                                        <p class="font-medium text-gray-900">${group.location || '-'}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Animais</p>
                                        <p class="font-medium text-gray-900">${group.current_count || 0}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Capacidade</p>
                                        <p class="font-medium text-gray-900">${group.capacity || 'Ilimitada'}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500">Ordem de Ordenha</p>
                                        <p class="font-medium text-gray-900">${group.milking_order || '-'}</p>
                                    </div>
                                </div>
                                ${group.description ? `<p class="mt-4 text-sm text-gray-700"><strong>Descrição:</strong> ${group.description}</p>` : ''}
                                ${group.feed_protocol ? `<p class="mt-2 text-sm text-gray-700"><strong>Protocolo de Alimentação:</strong> ${group.feed_protocol}</p>` : ''}
                            </div>
                            
                            <!-- Animais do Grupo -->
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-lg font-semibold text-gray-900">Animais do Grupo (${animals.length})</h4>
                                    <button onclick="openMoveAnimalsModal(${id})" class="px-4 py-2 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors text-sm font-medium">
                                        Mover Animais
                                    </button>
                                </div>
                                ${animals.length > 0 ? `
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Número</th>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Nome</th>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Raça</th>
                                                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700">Status</th>
                                                    <th class="px-3 py-2 text-center text-xs font-semibold text-gray-700">Ações</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                ${animals.map(animal => `
                                                    <tr>
                                                        <td class="px-3 py-2 text-gray-900">${animal.animal_number}</td>
                                                        <td class="px-3 py-2 text-gray-700">${animal.name || '-'}</td>
                                                        <td class="px-3 py-2 text-gray-700">${animal.breed || '-'}</td>
                                                        <td class="px-3 py-2 text-gray-700">${animal.status || '-'}</td>
                                                        <td class="px-3 py-2 text-center">
                                                            <button onclick="removeAnimalFromGroup(${animal.id}, ${id})" class="text-red-600 hover:text-red-800 text-xs font-medium">
                                                                Remover
                                                            </button>
                                                        </td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                ` : '<p class="text-gray-500 text-center py-4">Nenhum animal neste grupo</p>'}
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('group-details-modal').classList.remove('hidden');
                } else {
                    alert('Erro ao carregar detalhes: ' + (groupResult.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao carregar detalhes:', error);
                alert('Erro ao carregar detalhes do grupo');
            }
        }

        // Fechar detalhes
        function closeGroupDetails() {
            document.getElementById('group-details-modal').classList.add('hidden');
        }

        // Remover animal do grupo
        async function removeAnimalFromGroup(animalId, groupId) {
            if (!confirm('Tem certeza que deseja remover este animal do grupo?')) return;
            
            try {
                const response = await fetch(`${API_BASE}?action=remove_animals`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        animal_ids: [animalId]
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    viewGroupDetails(groupId);
                    loadGroups();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Animal removido do grupo!');
                    }
                } else {
                    alert('Erro ao remover: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao remover animal:', error);
                alert('Erro ao remover animal do grupo');
            }
        }

        // Abrir modal de mover animais
        async function openMoveAnimalsModal(groupId) {
            currentGroupId = groupId;
            
            // Carregar grupos para select
            const response = await fetch(`${API_BASE}?action=list`);
            const result = await response.json();
            
            if (result.success && result.data) {
                const select = document.getElementById('move-target-group');
                select.innerHTML = '<option value="">Selecione um grupo...</option>' + 
                    result.data
                        .filter(g => g.id != groupId)
                        .map(g => `<option value="${g.id}">${g.group_name} (${g.group_code || ''})</option>`)
                        .join('');
            }
            
            // Carregar animais do grupo atual
            const animalsResponse = await fetch(`${API_BASE}?action=animals&group_id=${groupId}`);
            const animalsResult = await animalsResponse.json();
            
            if (animalsResult.success && animalsResult.data) {
                selectedAnimals = animalsResult.data.map(a => a.id);
                const listDiv = document.getElementById('move-animals-list');
                
                if (animalsResult.data.length > 0) {
                    listDiv.innerHTML = `
                        <div class="space-y-2">
                            ${animalsResult.data.map(animal => `
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-900">${animal.animal_number} - ${animal.name || 'Sem nome'}</span>
                                    <input type="checkbox" checked onchange="toggleAnimalSelection(${animal.id})" class="rounded">
                                </div>
                            `).join('')}
                        </div>
                    `;
                } else {
                    listDiv.innerHTML = '<p class="text-gray-500 text-center">Nenhum animal neste grupo</p>';
                }
            }
            
            document.getElementById('move-animals-modal').classList.remove('hidden');
        }

        // Fechar modal de mover animais
        function closeMoveAnimalsModal() {
            document.getElementById('move-animals-modal').classList.add('hidden');
            selectedAnimals = [];
            currentGroupId = null;
        }

        // Toggle seleção de animal
        function toggleAnimalSelection(animalId) {
            const index = selectedAnimals.indexOf(animalId);
            if (index > -1) {
                selectedAnimals.splice(index, 1);
            } else {
                selectedAnimals.push(animalId);
            }
        }

        // Confirmar mover animais
        async function confirmMoveAnimals() {
            const targetGroupId = document.getElementById('move-target-group').value;
            
            if (!targetGroupId) {
                alert('Selecione um grupo de destino');
                return;
            }
            
            if (selectedAnimals.length === 0) {
                alert('Selecione pelo menos um animal');
                return;
            }
            
            try {
                const response = await fetch(`${API_BASE}?action=move_animals`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        animal_ids: selectedAnimals,
                        target_group_id: parseInt(targetGroupId)
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeMoveAnimalsModal();
                    if (currentGroupId) {
                        viewGroupDetails(currentGroupId);
                    }
                    loadGroups();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast(result.data.message || 'Animais movidos com sucesso!');
                    } else {
                        alert(result.data.message || 'Animais movidos com sucesso!');
                    }
                } else {
                    alert('Erro ao mover: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao mover animais:', error);
                alert('Erro ao mover animais');
            }
        }

        // Submeter formulário
        document.getElementById('group-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            formData.forEach((value, key) => {
                if (value !== '') {
                    data[key] = value;
                }
            });
            
            // Converter valores numéricos
            if (data.capacity) data.capacity = parseInt(data.capacity);
            if (data.milking_order) data.milking_order = parseInt(data.milking_order);
            
            try {
                const url = currentEditId 
                    ? `${API_BASE}?action=update`
                    : `${API_BASE}?action=create`;
                
                if (currentEditId) {
                    data.id = currentEditId;
                }
                
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closeGroupForm();
                    loadGroups();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Grupo salvo com sucesso!');
                    } else {
                        alert('Grupo salvo com sucesso!');
                    }
                } else {
                    alert('Erro ao salvar: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao salvar grupo:', error);
                alert('Erro ao salvar grupo');
            }
        });

        // Funções auxiliares
        function formatType(type) {
            const types = {
                'lactante': 'Lactante',
                'seco': 'Seco',
                'novilha': 'Novilha',
                'pre_parto': 'Pré-parto',
                'pos_parto': 'Pós-parto',
                'hospital': 'Hospital',
                'quarentena': 'Quarentena',
                'pasto': 'Pasto',
                'outros': 'Outros'
            };
            return types[type] || type;
        }

        function getTypeColor(type) {
            const colors = {
                'lactante': 'bg-green-100 text-green-800',
                'seco': 'bg-orange-100 text-orange-800',
                'novilha': 'bg-blue-100 text-blue-800',
                'pre_parto': 'bg-red-100 text-red-800',
                'pos_parto': 'bg-pink-100 text-pink-800',
                'hospital': 'bg-red-100 text-red-800',
                'quarentena': 'bg-yellow-100 text-yellow-800',
                'pasto': 'bg-green-100 text-green-800',
                'outros': 'bg-gray-100 text-gray-800'
            };
            return colors[type] || 'bg-gray-100 text-gray-800';
        }

        // Fechar modais ao clicar fora
        document.getElementById('group-form-modal').addEventListener('click', function(e) {
            if (e.target === this) closeGroupForm();
        });

        document.getElementById('group-details-modal').addEventListener('click', function(e) {
            if (e.target === this) closeGroupDetails();
        });

        document.getElementById('move-animals-modal').addEventListener('click', function(e) {
            if (e.target === this) closeMoveAnimalsModal();
        });
    </script>
</body>
</html>
