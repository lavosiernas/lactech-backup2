<?php
/**
 * Página: Central de Ações
 * Subpágina do Mais Opções - Sistema completo de gestão de ações pendentes e alertas
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
    <title>Central de Ações - LacTech</title>
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
        .pulse-dot {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
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
                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Central de Ações</h2>
                    <p class="text-sm text-gray-500">Tarefas prioritárias e alertas importantes</p>
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
            <!-- Resumo de Alertas -->
            <div class="mb-6 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl p-6 border border-orange-200 fade-in">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Resumo de Alertas</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-red-600" id="stat-urgent">0</p>
                        <p class="text-sm text-gray-600 mt-1">Urgentes</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-yellow-600" id="stat-pending">0</p>
                        <p class="text-sm text-gray-600 mt-1">Pendentes</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm text-center">
                        <p class="text-3xl font-bold text-blue-600" id="stat-monitoring">0</p>
                        <p class="text-sm text-gray-600 mt-1">Monitorar</p>
                    </div>
                </div>
            </div>

            <!-- Ações Prioritárias -->
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Ações Prioritárias</h3>
                    <button onclick="loadPriorityActions()" class="text-sm text-orange-600 hover:text-orange-700 font-medium flex items-center">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Atualizar
                    </button>
                </div>
                <div id="priority-actions-list" class="space-y-3">
                    <div class="text-center text-gray-500 py-8">
                        <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <p>Carregando ações...</p>
                    </div>
                </div>
            </div>

            <!-- Notificações -->
            <div class="mb-6 bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-bold text-gray-900">Notificações</h3>
                        <span id="notifications-badge" class="hidden px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-full pulse-dot">0</span>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="markAllNotificationsRead()" class="text-sm text-gray-600 hover:text-gray-800 font-medium">
                            Marcar todas como lidas
                        </button>
                        <button onclick="loadNotifications()" class="text-sm text-orange-600 hover:text-orange-700 font-medium flex items-center">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Atualizar
                        </button>
                    </div>
                </div>
                <div id="notifications-list" class="space-y-2 max-h-96 overflow-y-auto">
                    <div class="text-center text-gray-500 py-8">
                        <svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <p>Carregando notificações...</p>
                    </div>
                </div>
            </div>

            <!-- Ações Rápidas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 fade-in">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Ações Rápidas</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <button onclick="viewActionDetails('vaccination')" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-orange-50 hover:border-orange-300 transition-all border border-gray-200">
                        <svg class="w-6 h-6 text-orange-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Vacinações</p>
                            <p class="text-xs text-gray-600">Ver todas as vacinações pendentes</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="viewActionDetails('deworming')" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-yellow-50 hover:border-yellow-300 transition-all border border-gray-200">
                        <svg class="w-6 h-6 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Vermifugações</p>
                            <p class="text-xs text-gray-600">Ver todas as vermifugações pendentes</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="viewActionDetails('calving')" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-all border border-gray-200">
                        <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Partos Esperados</p>
                            <p class="text-xs text-gray-600">Monitorar partos previstos</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <button onclick="viewActionDetails('heat_expected')" class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-pink-50 hover:border-pink-300 transition-all border border-gray-200">
                        <svg class="w-6 h-6 text-pink-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                        <div class="flex-1 text-left">
                            <p class="font-medium text-gray-900">Cios Previstos</p>
                            <p class="text-xs text-gray-600">Cios esperados nos próximos 7 dias</p>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes -->
    <div id="action-details-modal" class="fixed inset-0 z-50 hidden modal-overlay">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto fade-in">
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900" id="action-details-title">Detalhes</h3>
                    <button onclick="closeActionDetails()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div id="action-details-content" class="p-6">
                    <p class="text-gray-500 text-center">Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../api/actions_center.php';

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            loadPriorityActions();
            loadNotifications();
            
            // Atualizar a cada 5 minutos
            setInterval(() => {
                loadDashboard();
                loadPriorityActions();
                loadNotifications();
            }, 300000);
        });

        // Carregar dashboard
        async function loadDashboard() {
            try {
                const response = await fetch(`${API_BASE}?action=dashboard`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const summary = result.data.summary;
                    document.getElementById('stat-urgent').textContent = summary.urgent || 0;
                    document.getElementById('stat-pending').textContent = summary.pending || 0;
                    document.getElementById('stat-monitoring').textContent = summary.monitoring || 0;
                }
            } catch (error) {
                console.error('Erro ao carregar dashboard:', error);
            }
        }

        // Carregar ações prioritárias
        async function loadPriorityActions() {
            const container = document.getElementById('priority-actions-list');
            container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const response = await fetch(`${API_BASE}?action=priority_actions`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const actions = result.data;
                    
                    if (actions.length > 0) {
                        container.innerHTML = actions.map(action => `
                            <div class="p-4 rounded-lg border-l-4 ${getPriorityColor(action.priority)} fade-in">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-medium ${getPriorityTextColor(action.priority)} text-sm">${action.title}</p>
                                        <p class="text-xs ${getPrioritySubTextColor(action.priority)} mt-1">${action.message}</p>
                                        ${action.animals && action.animals.length > 0 ? `
                                            <div class="mt-2 text-xs ${getPrioritySubTextColor(action.priority)}">
                                                <p class="font-medium">Animais:</p>
                                                <ul class="list-disc list-inside mt-1">
                                                    ${action.animals.slice(0, 3).map(a => `
                                                        <li>${a.animal_number || 'N/A'} - ${a.animal_name || 'Sem nome'}</li>
                                                    `).join('')}
                                                    ${action.animals.length > 3 ? `<li>... e mais ${action.animals.length - 3}</li>` : ''}
                                                </ul>
                                            </div>
                                        ` : ''}
                                    </div>
                                    <button onclick="viewActionDetails('${action.type}')" class="ml-3 px-4 py-2 ${getPriorityButtonColor(action.priority)} text-white text-xs rounded-lg hover:opacity-90 transition-colors font-medium">
                                        Ver Detalhes
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><p>Nenhuma ação prioritária no momento</p></div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar ações prioritárias:', error);
                container.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar ações</div>';
            }
        }

        // Carregar notificações
        async function loadNotifications() {
            const container = document.getElementById('notifications-list');
            container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 animate-spin mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><p>Carregando...</p></div>';
            
            try {
                const response = await fetch(`${API_BASE}?action=notifications&limit=20`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const notifications = result.data.notifications;
                    const unreadCount = result.data.unread_count || 0;
                    
                    // Atualizar badge
                    const badge = document.getElementById('notifications-badge');
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                    
                    if (notifications.length > 0) {
                        container.innerHTML = notifications.map(notif => `
                            <div class="p-3 rounded-lg border ${notif.is_read ? 'bg-gray-50 border-gray-200' : 'bg-white border-orange-200'} hover:shadow-sm transition-all fade-in">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-1">
                                            ${!notif.is_read ? '<span class="w-2 h-2 bg-orange-500 rounded-full"></span>' : ''}
                                            <p class="font-medium text-gray-900 text-sm">${notif.title || 'Notificação'}</p>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full ${getNotificationTypeColor(notif.type)}">${formatNotificationType(notif.type)}</span>
                                        </div>
                                        <p class="text-xs text-gray-600">${notif.message || ''}</p>
                                        <p class="text-xs text-gray-400 mt-1">${formatDate(notif.created_at)}</p>
                                    </div>
                                    ${!notif.is_read ? `
                                        <button onclick="markNotificationRead(${notif.id})" class="ml-2 text-xs text-orange-600 hover:text-orange-700 font-medium">
                                            Marcar lida
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('');
                    } else {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg><p>Nenhuma notificação</p></div>';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar notificações:', error);
                container.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar notificações</div>';
            }
        }

        // Ver detalhes de ação
        async function viewActionDetails(type) {
            const titles = {
                'vaccination': 'Vacinações Pendentes',
                'deworming': 'Vermifugações Pendentes',
                'calving': 'Partos Esperados',
                'heat_expected': 'Cios Previstos',
                'low_bcs': 'Animais com BCS Baixo'
            };
            
            document.getElementById('action-details-title').textContent = titles[type] || 'Detalhes';
            document.getElementById('action-details-modal').classList.remove('hidden');
            
            const content = document.getElementById('action-details-content');
            content.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando detalhes...</p>';
            
            try {
                const response = await fetch(`${API_BASE}?action=action_details&type=${type}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const details = result.data;
                    
                    if (details.length > 0) {
                        content.innerHTML = `
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Animal</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Raça</th>
                                            ${type === 'calving' ? '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Data Prevista</th>' : ''}
                                            ${type === 'vaccination' || type === 'deworming' ? '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Data</th>' : ''}
                                            ${type === 'heat_expected' ? '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Data do Cio</th>' : ''}
                                            ${type === 'low_bcs' ? '<th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">BCS</th>' : ''}
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Dias</th>
                                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        ${details.map(item => `
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 text-gray-900">${item.animal_number || 'N/A'}</td>
                                                <td class="px-4 py-3 text-gray-700">${item.name || '-'}</td>
                                                <td class="px-4 py-3 text-gray-700">${item.breed || '-'}</td>
                                                ${type === 'calving' ? `<td class="px-4 py-3 text-gray-700">${formatDate(item.expected_birth)}</td>` : ''}
                                                ${type === 'vaccination' || type === 'deworming' ? `<td class="px-4 py-3 text-gray-700">${formatDate(item.alert_date)}</td>` : ''}
                                                ${type === 'heat_expected' ? `<td class="px-4 py-3 text-gray-700">${formatDate(item.heat_date)}</td>` : ''}
                                                ${type === 'low_bcs' ? `<td class="px-4 py-3 text-gray-700"><span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-medium">${item.score || '-'}</span></td>` : ''}
                                                <td class="px-4 py-3 text-gray-700">
                                                    <span class="px-2 py-1 rounded text-xs font-medium ${getDaysColor(item.days_until || 0)}">
                                                        ${item.days_until !== undefined ? (item.days_until > 0 ? `Em ${item.days_until} dias` : item.days_until === 0 ? 'Hoje' : `Há ${Math.abs(item.days_until)} dias`) : '-'}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button onclick="viewAnimalDetails(${item.id})" class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                                        Ver Animal
                                                    </button>
                                                </td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `;
                    } else {
                        content.innerHTML = '<div class="text-center text-gray-500 py-8"><svg class="w-12 h-12 text-gray-300 mb-2 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg><p>Nenhum registro encontrado</p></div>';
                    }
                } else {
                    content.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar detalhes</div>';
                }
            } catch (error) {
                console.error('Erro ao carregar detalhes:', error);
                content.innerHTML = '<div class="text-center text-red-500 py-8">Erro ao carregar detalhes</div>';
            }
        }

        // Fechar detalhes
        function closeActionDetails() {
            document.getElementById('action-details-modal').classList.add('hidden');
        }

        // Marcar notificação como lida
        async function markNotificationRead(id) {
            try {
                const response = await fetch(`${API_BASE}?action=mark_notification_read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadNotifications();
                    loadDashboard();
                }
            } catch (error) {
                console.error('Erro ao marcar notificação:', error);
            }
        }

        // Marcar todas como lidas
        async function markAllNotificationsRead() {
            try {
                const response = await fetch(`${API_BASE}?action=mark_all_read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    loadNotifications();
                    loadDashboard();
                    if (typeof window.showSuccessToast === 'function') {
                        window.showSuccessToast('Todas as notificações foram marcadas como lidas!');
                    }
                }
            } catch (error) {
                console.error('Erro ao marcar notificações:', error);
            }
        }

        // Ver detalhes do animal
        function viewAnimalDetails(animalId) {
            // Implementar navegação para detalhes do animal
            alert('Funcionalidade de visualizar animal será implementada');
        }

        // Funções auxiliares
        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function getPriorityColor(priority) {
            const colors = {
                'high': 'bg-red-50 border-red-400',
                'medium': 'bg-yellow-50 border-yellow-400',
                'low': 'bg-blue-50 border-blue-400'
            };
            return colors[priority] || 'bg-gray-50 border-gray-400';
        }

        function getPriorityTextColor(priority) {
            const colors = {
                'high': 'text-red-800',
                'medium': 'text-yellow-800',
                'low': 'text-blue-800'
            };
            return colors[priority] || 'text-gray-800';
        }

        function getPrioritySubTextColor(priority) {
            const colors = {
                'high': 'text-red-600',
                'medium': 'text-yellow-600',
                'low': 'text-blue-600'
            };
            return colors[priority] || 'text-gray-600';
        }

        function getPriorityButtonColor(priority) {
            const colors = {
                'high': 'bg-red-600',
                'medium': 'bg-yellow-600',
                'low': 'bg-blue-600'
            };
            return colors[priority] || 'bg-gray-600';
        }

        function getNotificationTypeColor(type) {
            const colors = {
                'info': 'bg-blue-100 text-blue-800',
                'warning': 'bg-yellow-100 text-yellow-800',
                'error': 'bg-red-100 text-red-800',
                'success': 'bg-green-100 text-green-800'
            };
            return colors[type] || 'bg-gray-100 text-gray-800';
        }

        function formatNotificationType(type) {
            const types = {
                'info': 'Info',
                'warning': 'Aviso',
                'error': 'Erro',
                'success': 'Sucesso'
            };
            return types[type] || type;
        }

        function getDaysColor(days) {
            if (days < 0) return 'bg-red-100 text-red-800';
            if (days === 0) return 'bg-orange-100 text-orange-800';
            if (days <= 7) return 'bg-yellow-100 text-yellow-800';
            return 'bg-green-100 text-green-800';
        }

        // Fechar modal ao clicar fora
        document.getElementById('action-details-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeActionDetails();
            }
        });
    </script>
</body>
</html>
