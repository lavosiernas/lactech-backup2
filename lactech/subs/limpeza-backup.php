<?php
/**
 * Limpeza e Backup - Sistema Lactech
 * Página para gerenciar limpeza de dados e backups
 */

session_start();

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../includes/Database.class.php';
require_once __DIR__ . '/../includes/config.php';

$db = Database::getInstance();

// Obter dados do usuário
$user_id = $_SESSION['user_id'] ?? 1;
$farm_id = $_SESSION['farm_id'] ?? 1;

$pageTitle = 'Limpeza e Backup';
$v = time(); // Para cache busting
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - LacTech</title>
    <?php if (file_exists(__DIR__ . '/../assets/css/tailwind.min.css')): ?>
        <link rel="stylesheet" href="../assets/css/tailwind.min.css">
    <?php else: ?>
        <script src="https://cdn.tailwindcss.com"></script>
    <?php endif; ?>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo $v; ?>">
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <button onclick="closePage()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Limpeza e Backup</h1>
                            <p class="text-sm text-gray-500 mt-1">Gerencie a limpeza de dados e backups do sistema</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Aviso de Segurança -->
            <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-yellow-800 mb-1">Atenção</h3>
                        <p class="text-sm text-yellow-700">
                            As ações de limpeza são <strong>irreversíveis</strong>. 
                            Usuários e fazendas <strong>nunca são apagados</strong> por segurança.
                            Certifique-se de fazer backup antes de limpar dados importantes.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Seção de Limpeza -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Limpeza de Dados</h2>
                        <p class="text-sm text-gray-500">Selecione as categorias de dados que deseja limpar</p>
                    </div>
                </div>

                <div id="cleanupCategoriesContainer" class="space-y-3 mb-6">
                    <!-- Categorias serão carregadas via JavaScript -->
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-3 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>Carregando categorias...</p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="validateAndShowConfirm()" id="cleanupBtn" class="flex-1 px-6 py-3 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Limpar Dados Selecionados
                    </button>
                </div>

                <div id="cleanupResult" class="mt-6 hidden"></div>
            </div>

            <!-- Seção de Backup (Futuro) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Backup</h2>
                        <p class="text-sm text-gray-500">Funcionalidade de backup em desenvolvimento</p>
                    </div>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-600">A funcionalidade de backup será implementada em breve.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal de Confirmação -->
    <div id="confirmCleanupModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeConfirmModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full z-10">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900">Confirmar Limpeza</h3>
                        <p class="text-sm text-gray-500 mt-1">Esta ação não pode ser desfeita</p>
                    </div>
                </div>
                
                <div class="mb-6">
                    <p class="text-sm text-gray-700 mb-3">
                        Você está prestes a limpar os dados das seguintes categorias:
                    </p>
                    <div id="confirmCategoriesList" class="bg-gray-50 rounded-lg p-3 mb-4 max-h-40 overflow-y-auto">
                        <!-- Categorias serão listadas aqui -->
                    </div>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-yellow-800">Atenção</p>
                                <p class="text-xs text-yellow-700 mt-1">
                                    Usuários e fazendas <strong>não serão apagados</strong>. Todos os outros dados selecionados serão permanentemente removidos.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="button" 
                        onclick="closeConfirmModal()" 
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="button" 
                        onclick="confirmAndExecuteCleanup()" 
                        id="confirmCleanupBtn"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Confirmar e Limpar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/toast-notifications.js?v=<?php echo $v; ?>"></script>
    <script>
        const CLEANUP_API = '../api/cleanup.php';

        // Fechar página
        function closePage() {
            if (window.parent && window.parent !== window) {
                // Está em iframe, fechar modal
                window.parent.postMessage({type: 'closeModal'}, '*');
            } else {
                // Está em página direta, voltar
                window.history.back();
            }
        }

        // Carregar categorias
        async function loadCleanupCategories() {
            const container = document.getElementById('cleanupCategoriesContainer');
            if (!container) return;
            
            try {
                const response = await fetch(CLEANUP_API);
                const result = await response.json();
                
                if (result.success && result.categories) {
                    container.innerHTML = '';
                    
                    Object.keys(result.categories).forEach(key => {
                        const category = result.categories[key];
                        const checkboxId = `cleanup_${key}`;
                        
                        const div = document.createElement('div');
                        div.className = 'flex items-start gap-4 p-4 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition-colors';
                        
                        div.innerHTML = `
                            <input 
                                type="checkbox" 
                                id="${checkboxId}" 
                                value="${key}" 
                                class="mt-1 w-5 h-5 text-red-600 border-gray-300 rounded focus:ring-red-500"
                                ${key === 'tudo' ? 'onchange="handleCleanupTudoChange(this)"' : ''}
                            >
                            <label for="${checkboxId}" class="flex-1 cursor-pointer">
                                <div class="font-semibold text-base text-gray-900 mb-1">${category.name}</div>
                                <div class="text-sm text-gray-600 mb-2">${category.description}</div>
                                ${category.tables ? `<div class="text-xs text-gray-500 mt-2"><strong>Tabelas:</strong> ${category.tables.join(', ')}</div>` : ''}
                                ${category.tables_count ? `<div class="text-xs text-gray-500 mt-1"><strong>Total:</strong> ${category.tables_count} tabelas</div>` : ''}
                            </label>
                        `;
                        
                        container.appendChild(div);
                    });
                } else {
                    container.innerHTML = '<p class="text-center text-red-600 py-8">Erro ao carregar categorias</p>';
                }
            } catch (error) {
                console.error('Erro ao carregar categorias de limpeza:', error);
                if (container) {
                    container.innerHTML = '<p class="text-center text-red-600 py-8">Erro ao conectar ao servidor</p>';
                }
            }
        }

        function handleCleanupTudoChange(checkbox) {
            const container = document.getElementById('cleanupCategoriesContainer');
            if (!container) return;
            
            const allCheckboxes = container.querySelectorAll('input[type="checkbox"]');
            
            if (checkbox.checked) {
                // Se "Tudo" foi marcado, desmarcar todos os outros
                allCheckboxes.forEach(cb => {
                    if (cb !== checkbox) {
                        cb.checked = false;
                    }
                });
            }
        }

        let pendingCleanupCategories = [];

        function openConfirmModal(categories) {
            const modal = document.getElementById('confirmCleanupModal');
            const categoriesList = document.getElementById('confirmCategoriesList');
            
            if (!modal || !categoriesList) return;
            
            // Armazenar categorias para execução
            pendingCleanupCategories = categories;
            
            // Listar categorias no modal
            categoriesList.innerHTML = '';
            categories.forEach(cat => {
                const container = document.getElementById('cleanupCategoriesContainer');
                let categoryName = cat;
                if (cat === 'tudo') {
                    categoryName = 'Tudo (todas as categorias)';
                } else {
                    const label = container?.querySelector(`label[for="cleanup_${cat}"] .font-semibold`);
                    if (label) categoryName = label.textContent;
                }
                
                const item = document.createElement('div');
                item.className = 'flex items-center gap-2 py-2 border-b border-gray-200 last:border-0';
                item.innerHTML = `
                    <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span class="text-sm text-gray-700">${categoryName}</span>
                `;
                categoriesList.appendChild(item);
            });
            
            // Mostrar modal
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirmCleanupModal');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        async function confirmAndExecuteCleanup() {
            closeConfirmModal();
            
            // Se "Tudo" está selecionado, pedir confirmação extra
            if (pendingCleanupCategories.includes('tudo')) {
                const extraConfirm = confirm(
                    '⚠️ ATENÇÃO EXTREMA!\n\n' +
                    'Você está prestes a limpar TODOS os dados do sistema (exceto usuários e fazendas).\n\n' +
                    'Esta ação é IRREVERSÍVEL!\n\n' +
                    'Tem certeza absoluta?'
                );
                
                if (!extraConfirm) {
                    pendingCleanupCategories = [];
                    return;
                }
            }
            
            // Executar limpeza
            await executeCleanup();
        }

        async function executeCleanup() {
            const container = document.getElementById('cleanupCategoriesContainer');
            const resultDiv = document.getElementById('cleanupResult');
            const cleanupBtn = document.getElementById('cleanupBtn');
            
            if (!container || !resultDiv) return;
            
            // Usar categorias pendentes ou coletar do formulário
            let selectedCategories = pendingCleanupCategories.length > 0 
                ? pendingCleanupCategories 
                : Array.from(container.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);
            
            if (selectedCategories.length === 0) {
                resultDiv.className = 'mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg';
                resultDiv.innerHTML = '<p class="text-sm text-yellow-800">Selecione pelo menos uma categoria para limpar.</p>';
                resultDiv.classList.remove('hidden');
                pendingCleanupCategories = [];
                return;
            }
            
            // Desabilitar botão
            cleanupBtn.disabled = true;
            cleanupBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Limpando...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'cleanup');
                selectedCategories.forEach(cat => formData.append('categories[]', cat));
                
                const response = await fetch(CLEANUP_API, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.className = 'mt-6 p-6 bg-green-50 border border-green-200 rounded-lg';
                    let html = '<div class="flex items-start gap-4">';
                    html += '<svg class="w-6 h-6 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                    html += '<div class="flex-1">';
                    html += '<h3 class="font-semibold text-lg text-green-800 mb-2">Limpeza concluída com sucesso!</h3>';
                    html += `<p class="text-sm text-green-700 mb-3">Tabelas limpas: <strong>${result.cleaned_tables.length}</strong></p>`;
                    if (result.cleaned_tables.length > 0) {
                        html += '<div class="bg-white rounded-lg p-4 border border-green-200">';
                        html += '<p class="text-xs font-semibold text-green-800 mb-2">Tabelas processadas:</p>';
                        html += '<ul class="text-xs text-green-700 space-y-1">';
                        result.cleaned_tables.forEach(table => {
                            html += `<li class="flex items-center gap-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>${table}</li>`;
                        });
                        html += '</ul></div>';
                    }
                    if (result.errors && result.errors.length > 0) {
                        html += '<div class="mt-4 pt-4 border-t border-green-200">';
                        html += '<p class="text-sm text-yellow-700 font-semibold mb-2">Avisos:</p>';
                        html += '<ul class="text-sm text-yellow-600 space-y-1 list-disc list-inside">';
                        result.errors.forEach(error => {
                            html += `<li>${error}</li>`;
                        });
                        html += '</ul></div>';
                    }
                    html += '</div></div>';
                    resultDiv.innerHTML = html;
                    resultDiv.classList.remove('hidden');
                    
                    // Desmarcar todos os checkboxes
                    container.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
                    
                    // Limpar categorias pendentes
                    pendingCleanupCategories = [];
                    
                    // Mostrar toast de sucesso
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast('Limpeza concluída com sucesso!', 'Sucesso');
                    }
                    
                } else {
                    resultDiv.className = 'mt-6 p-6 bg-red-50 border border-red-200 rounded-lg';
                    resultDiv.innerHTML = `
                        <div class="flex items-start gap-4">
                            <svg class="w-6 h-6 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg text-red-800 mb-2">Erro ao executar limpeza</h3>
                                <p class="text-sm text-red-700">${result.error || 'Erro desconhecido'}</p>
                            </div>
                        </div>
                    `;
                    resultDiv.classList.remove('hidden');
                    
                    if (typeof showErrorToast === 'function') {
                        showErrorToast(result.error || 'Erro ao executar limpeza', 'Erro');
                    }
                }
            } catch (error) {
                console.error('Erro ao executar limpeza:', error);
                resultDiv.className = 'mt-6 p-6 bg-red-50 border border-red-200 rounded-lg';
                resultDiv.innerHTML = `
                    <div class="flex items-start gap-4">
                        <svg class="w-6 h-6 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-semibold text-lg text-red-800 mb-2">Erro de conexão</h3>
                            <p class="text-sm text-red-700">Não foi possível conectar ao servidor. Tente novamente.</p>
                        </div>
                    </div>
                `;
                resultDiv.classList.remove('hidden');
                
                if (typeof showErrorToast === 'function') {
                    showErrorToast('Erro de conexão ao servidor', 'Erro');
                }
            } finally {
                cleanupBtn.disabled = false;
                cleanupBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Limpar Dados Selecionados
                `;
            }
        }

        function validateAndShowConfirm() {
            const container = document.getElementById('cleanupCategoriesContainer');
            const resultDiv = document.getElementById('cleanupResult');
            
            if (!container) return;
            
            // Coletar categorias selecionadas
            const checkboxes = container.querySelectorAll('input[type="checkbox"]:checked');
            const selectedCategories = Array.from(checkboxes).map(cb => cb.value);
            
            if (selectedCategories.length === 0) {
                resultDiv.className = 'mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg';
                resultDiv.innerHTML = '<p class="text-sm text-yellow-800">Selecione pelo menos uma categoria para limpar.</p>';
                resultDiv.classList.remove('hidden');
                return;
            }
            
            // Esconder resultado anterior
            resultDiv.classList.add('hidden');
            
            // Abrir modal de confirmação
            openConfirmModal(selectedCategories);
        }

        // Carregar categorias ao abrir a página
        document.addEventListener('DOMContentLoaded', function() {
            loadCleanupCategories();
            
            // Fechar modal ao pressionar ESC
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeConfirmModal();
                }
            });
        });
    </script>
</body>
</html>

