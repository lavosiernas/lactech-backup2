<?php
/**
 * Página: Relatórios
 * Sistema completo de geração de relatórios com exportação Excel e PDF
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
    <title>Relatórios - LacTech</title>
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
                            <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
                                <p class="text-sm text-gray-600">Gere relatórios detalhados em Excel e PDF</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="container mx-auto px-6 py-6">
            <!-- Configuração de Logo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Configuração da Logo da Fazenda</h2>
                <p class="text-sm text-gray-600 mb-4">A logo será exibida nos relatórios em PDF. Formatos JPG/JPEG terão o fundo removido automaticamente.</p>
                
                <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                    <!-- Preview da Logo -->
                    <div class="flex-shrink-0">
                        <div class="w-32 h-32 border-2 border-gray-300 rounded-lg flex items-center justify-center bg-gray-50 overflow-hidden">
                            <img id="logo-preview" src="" alt="Logo da Fazenda" class="max-w-full max-h-full object-contain hidden">
                            <div id="logo-placeholder" class="text-gray-400 text-center p-4">
                                <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <p class="text-xs">Nenhuma logo</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Upload Controls -->
                    <div class="flex-1">
                        <form id="logo-upload-form" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Selecione uma imagem (JPG, JPEG ou PNG)
                                </label>
                                <input 
                                    type="file" 
                                    id="logo-file-input" 
                                    name="logo" 
                                    accept="image/jpeg,image/jpg,image/png"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    onchange="handleLogoFileSelect(event)"
                                >
                                <p class="mt-1 text-xs text-gray-500">Tamanho máximo: 5MB. JPG/JPEG terá fundo removido automaticamente.</p>
                            </div>
                            
                            <div class="flex gap-3">
                                <button 
                                    type="button"
                                    onclick="uploadLogo()" 
                                    id="upload-logo-btn"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled
                                >
                                    Enviar Logo
                                </button>
                                <button 
                                    type="button"
                                    onclick="deleteLogo()" 
                                    id="delete-logo-btn"
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium hidden"
                                >
                                    Remover Logo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Seleção de Relatório -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Selecione o Tipo de Relatório</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="report-types-container">
                    <p class="text-gray-500 text-center py-8 col-span-full">Carregando tipos de relatórios...</p>
                </div>
            </div>

            <!-- Configurações do Relatório -->
            <div id="report-config" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 hidden">
                <h2 class="text-lg font-bold text-gray-900 mb-4">Configurações do Relatório</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Inicial</label>
                        <input type="date" id="date-from" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Final</label>
                        <input type="date" id="date-to" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <!-- Filtros Específicos -->
                <div id="specific-filters" class="mb-6">
                    <!-- Filtros serão adicionados dinamicamente baseado no tipo de relatório -->
                </div>

                <!-- Botões de Exportação -->
                <div class="flex flex-wrap gap-4">
                    <button onclick="exportReport('excel')" class="flex-1 md:flex-none px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Exportar para Excel</span>
                    </button>
                    <button onclick="exportReport('pdf')" class="flex-1 md:flex-none px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        <span>Exportar para PDF</span>
                    </button>
                    <button onclick="previewReport()" class="flex-1 md:flex-none px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>Visualizar</span>
                    </button>
                </div>
            </div>

            <!-- Preview do Relatório -->
            <div id="report-preview" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hidden">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">Pré-visualização</h2>
                    <button onclick="closePreview()" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="preview-content" class="overflow-x-auto">
                    <p class="text-gray-500 text-center py-8">Carregando pré-visualização...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toastContainer" class="fixed bottom-4 right-4 z-[99999]"></div>

    <style>
        /* Toast Notifications Styles */
        #toastContainer {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }

        .toast {
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15), 0 4px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            min-width: 320px;
            max-width: 420px;
            pointer-events: auto;
            transform: translateX(450px);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border-left: 4px solid;
            position: relative;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.success {
            border-left-color: #10b981;
            background: linear-gradient(to right, #f0fdf4 0%, white 5%);
        }

        .toast.error {
            border-left-color: #ef4444;
            background: linear-gradient(to right, #fef2f2 0%, white 5%);
        }

        .toast.warning {
            border-left-color: #f59e0b;
            background: linear-gradient(to right, #fffbeb 0%, white 5%);
        }

        .toast.info {
            border-left-color: #3b82f6;
            background: linear-gradient(to right, #eff6ff 0%, white 5%);
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .toast.success .toast-icon {
            color: #10b981;
        }

        .toast.error .toast-icon {
            color: #ef4444;
        }

        .toast.warning .toast-icon {
            color: #f59e0b;
        }

        .toast.info .toast-icon {
            color: #3b82f6;
        }

        .toast-content {
            flex: 1;
            min-width: 0;
        }

        .toast-title {
            font-weight: 600;
            font-size: 14px;
            color: #111827;
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .toast-message {
            font-size: 13px;
            color: #6b7280;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .toast-close {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
            flex-shrink: 0;
            margin-top: -4px;
            margin-right: -4px;
        }

        .toast-close:hover {
            background: rgba(0, 0, 0, 0.05);
            color: #374151;
        }

        .toast-close:active {
            transform: scale(0.95);
        }

        @media (max-width: 640px) {
            #toastContainer {
                left: 16px;
                right: 16px;
                bottom: 16px;
            }

            .toast {
                min-width: auto;
                max-width: none;
                width: 100%;
            }
        }
    </style>

    <script src="../assets/js/toast-notifications.js?v=<?php echo $v; ?>"></script>
    <script>
        const API_BASE = '../api/reports.php';
        const LOGO_API_BASE = '../api/farm_logo.php';
        let currentReportType = null;
        let reportData = null;
        let selectedLogoFile = null;

        // Carregar tipos de relatórios
        document.addEventListener('DOMContentLoaded', () => {
            loadReportTypes();
            setDefaultDates();
            loadCurrentLogo();
        });

        // Funções para gerenciamento de logo
        function handleLogoFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            selectedLogoFile = file;
            document.getElementById('upload-logo-btn').disabled = false;
            
            // Preview
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('logo-preview');
                const placeholder = document.getElementById('logo-placeholder');
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        async function loadCurrentLogo() {
            try {
                const response = await fetch(`${LOGO_API_BASE}?action=get`);
                const result = await response.json();
                
                if (result.success && result.data && result.data.logo_path) {
                    const preview = document.getElementById('logo-preview');
                    const placeholder = document.getElementById('logo-placeholder');
                    preview.src = '../' + result.data.logo_path + '?t=' + Date.now();
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                    document.getElementById('delete-logo-btn').classList.remove('hidden');
                }
            } catch (error) {
                console.error('Erro ao carregar logo:', error);
            }
        }

        async function uploadLogo() {
                if (!selectedLogoFile) {
                if (typeof showErrorToast === 'function') {
                    showErrorToast('Por favor, selecione uma imagem antes de enviar.', 'Arquivo não selecionado');
                } else {
                    alert('Selecione um arquivo primeiro');
                }
                return;
            }

            const formData = new FormData();
            formData.append('logo', selectedLogoFile);
            formData.append('action', 'upload');

            try {
                const uploadBtn = document.getElementById('upload-logo-btn');
                uploadBtn.disabled = true;
                uploadBtn.textContent = 'Enviando...';

                const response = await fetch(LOGO_API_BASE, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast('Logo da fazenda salva e pronta para uso nos relatórios!', 'Logo enviada');
                    } else {
                        alert('Logo enviada com sucesso!');
                    }
                    await loadCurrentLogo();
                    document.getElementById('logo-file-input').value = '';
                    selectedLogoFile = null;
                    uploadBtn.disabled = true;
                } else {
                    if (typeof showErrorToast === 'function') {
                        showErrorToast(result.error || 'Não foi possível enviar a logo. Verifique o arquivo e tente novamente.', 'Erro ao enviar logo');
                    } else {
                        alert(result.error || 'Erro ao enviar logo');
                    }
                    uploadBtn.disabled = false;
                }
                uploadBtn.textContent = 'Enviar Logo';
            } catch (error) {
                console.error('Erro ao enviar logo:', error);
                if (typeof showErrorToast === 'function') {
                    showErrorToast('Ocorreu um erro ao processar o envio. Tente novamente.', 'Erro ao enviar logo');
                } else {
                    alert('Erro ao enviar logo');
                }
                document.getElementById('upload-logo-btn').disabled = false;
                document.getElementById('upload-logo-btn').textContent = 'Enviar Logo';
            }
        }

        async function deleteLogo() {
            if (!confirm('Tem certeza que deseja remover a logo?')) {
                return;
            }

            try {
                const response = await fetch(`${LOGO_API_BASE}?action=delete`, {
                    method: 'POST'
                });

                const result = await response.json();

                if (result.success) {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast('Logo removida. Os próximos relatórios não exibirão logo.', 'Logo removida');
                    } else {
                        alert('Logo removida com sucesso!');
                    }
                    const preview = document.getElementById('logo-preview');
                    const placeholder = document.getElementById('logo-placeholder');
                    preview.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                    document.getElementById('delete-logo-btn').classList.add('hidden');
                    document.getElementById('logo-file-input').value = '';
                    selectedLogoFile = null;
                } else {
                    if (typeof showErrorToast === 'function') {
                        showErrorToast(result.error || 'Não foi possível remover a logo. Tente novamente.', 'Erro ao remover logo');
                    } else {
                        alert(result.error || 'Erro ao remover logo');
                    }
                }
            } catch (error) {
                console.error('Erro ao remover logo:', error);
                if (typeof showErrorToast === 'function') {
                    showErrorToast('Ocorreu um erro ao processar a remoção. Tente novamente.', 'Erro ao remover logo');
                } else {
                    alert('Erro ao remover logo');
                }
            }
        }

        function setDefaultDates() {
            const today = new Date();
            const lastMonth = new Date();
            lastMonth.setMonth(lastMonth.getMonth() - 1);
            
            document.getElementById('date-from').value = lastMonth.toISOString().split('T')[0];
            document.getElementById('date-to').value = today.toISOString().split('T')[0];
        }

        async function loadReportTypes() {
            try {
                const response = await fetch(`${API_BASE}?action=list_types`);
                const result = await response.json();
                
                if (result.success) {
                    const container = document.getElementById('report-types-container');
                    container.innerHTML = result.data.map(type => `
                        <div onclick="selectReportType('${type.id}')" class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 cursor-pointer transition-all report-type-card" data-type="${type.id}">
                            <div class="flex items-center space-x-3 mb-2">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-bold text-gray-900">${type.name}</h3>
                            </div>
                            <p class="text-sm text-gray-600">${type.description}</p>
                        </div>
                    `).join('');
                }
            } catch (error) {
                console.error('Erro ao carregar tipos:', error);
            }
        }

        function selectReportType(type) {
            currentReportType = type;
            
            // Atualizar visual
            document.querySelectorAll('.report-type-card').forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
            });
            
            const selectedCard = document.querySelector(`[data-type="${type}"]`);
            if (selectedCard) {
                selectedCard.classList.add('border-blue-500', 'bg-blue-50');
                selectedCard.classList.remove('border-gray-200');
            }
            
            // Mostrar configurações
            document.getElementById('report-config').classList.remove('hidden');
            
            // Carregar filtros específicos
            loadSpecificFilters(type);
        }

        function loadSpecificFilters(type) {
            const container = document.getElementById('specific-filters');
            
            switch (type) {
                case 'animals':
                    container.innerHTML = `
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select id="filter-status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Todos</option>
                                    <option value="Lactante">Lactante</option>
                                    <option value="Seca">Seca</option>
                                    <option value="Novilha">Novilha</option>
                                    <option value="Bezerro">Bezerro</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Raça</label>
                                <input type="text" id="filter-breed" placeholder="Filtrar por raça" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    `;
                    break;
                default:
                    container.innerHTML = '';
                    break;
            }
        }

        async function exportReport(format) {
            if (!currentReportType) {
                showErrorToast('Selecione um tipo de relatório primeiro');
                return;
            }
            
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            
            if (!dateFrom || !dateTo) {
                showErrorToast('Selecione o período do relatório');
                return;
            }
            
            // Coletar filtros
            const filters = {};
            if (currentReportType === 'animals') {
                const status = document.getElementById('filter-status')?.value;
                const breed = document.getElementById('filter-breed')?.value;
                if (status) filters.status = status;
                if (breed) filters.breed = breed;
            }
            
            // Criar formulário para download
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = API_BASE;
            form.target = '_blank';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = `export_${format}`;
            form.appendChild(actionInput);
            
            const typeInput = document.createElement('input');
            typeInput.type = 'hidden';
            typeInput.name = 'report_type';
            typeInput.value = currentReportType;
            form.appendChild(typeInput);
            
            const dateFromInput = document.createElement('input');
            dateFromInput.type = 'hidden';
            dateFromInput.name = 'date_from';
            dateFromInput.value = dateFrom;
            form.appendChild(dateFromInput);
            
            const dateToInput = document.createElement('input');
            dateToInput.type = 'hidden';
            dateToInput.name = 'date_to';
            dateToInput.value = dateTo;
            form.appendChild(dateToInput);
            
            const filtersInput = document.createElement('input');
            filtersInput.type = 'hidden';
            filtersInput.name = 'filters';
            filtersInput.value = JSON.stringify(filters);
            form.appendChild(filtersInput);
            
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
            
            showSuccessToast(`Exportando relatório em ${format.toUpperCase()}...`);
        }

        async function previewReport() {
            if (!currentReportType) {
                showErrorToast('Selecione um tipo de relatório primeiro');
                return;
            }
            
            const dateFrom = document.getElementById('date-from').value;
            const dateTo = document.getElementById('date-to').value;
            
            if (!dateFrom || !dateTo) {
                showErrorToast('Selecione o período do relatório');
                return;
            }
            
            const previewDiv = document.getElementById('report-preview');
            const contentDiv = document.getElementById('preview-content');
            
            previewDiv.classList.remove('hidden');
            contentDiv.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando dados...</p>';
            
            try {
                const params = new URLSearchParams();
                params.append('action', 'get_data');
                params.append('report_type', currentReportType);
                params.append('date_from', dateFrom);
                params.append('date_to', dateTo);
                
                // Adicionar filtros
                const filters = {};
                if (currentReportType === 'animals') {
                    const status = document.getElementById('filter-status')?.value;
                    const breed = document.getElementById('filter-breed')?.value;
                    if (status) filters.status = status;
                    if (breed) filters.breed = breed;
                }
                if (Object.keys(filters).length > 0) {
                    params.append('filters', JSON.stringify(filters));
                }
                
                const response = await fetch(`${API_BASE}?${params.toString()}`);
                const result = await response.json();
                
                if (result.success) {
                    reportData = result.data;
                    renderPreview(result.data);
                } else {
                    contentDiv.innerHTML = `<p class="text-red-500 text-center py-8">${result.message || 'Erro ao carregar dados'}</p>`;
                }
            } catch (error) {
                contentDiv.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar pré-visualização</p>';
                console.error('Erro:', error);
            }
        }

        function renderPreview(data) {
            const contentDiv = document.getElementById('preview-content');
            
            switch (currentReportType) {
                case 'production':
                    if (data.daily && data.daily.length > 0) {
                        let html = '<div class="mb-4"><h3 class="font-bold text-gray-900 mb-2">Resumo</h3>';
                        if (data.summary) {
                            html += `<p>Total de Animais: ${data.summary.total_animals || 0}</p>`;
                            html += `<p>Volume Total: ${parseFloat(data.summary.total_volume || 0).toFixed(2)} L</p>`;
                            html += `<p>Média Diária: ${parseFloat(data.summary.avg_volume || 0).toFixed(2)} L</p>`;
                        }
                        html += '</div>';
                        html += '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animais</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volume (L)</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Média (L)</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gordura (%)</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proteína (%)</th>';
                        html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                        
                        data.daily.forEach(row => {
                            html += '<tr>';
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${formatDate(row.date)}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${row.animals_count}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${parseFloat(row.total_volume).toFixed(2)}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${parseFloat(row.avg_volume).toFixed(2)}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${parseFloat(row.avg_fat || 0).toFixed(2)}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${parseFloat(row.avg_protein || 0).toFixed(2)}</td>`;
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        contentDiv.innerHTML = html;
                    } else {
                        contentDiv.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum dado encontrado para o período selecionado</p>';
                    }
                    break;
                    
                case 'animals':
                    if (data.animals && data.animals.length > 0) {
                        let html = '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Número</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Raça</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saúde</th>';
                        html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                        
                        data.animals.forEach(animal => {
                            html += '<tr>';
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${animal.animal_number || '-'}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${animal.name || '-'}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${animal.breed || '-'}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${animal.status || '-'}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm">${animal.health_status || '-'}</td>`;
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        contentDiv.innerHTML = html;
                    } else {
                        contentDiv.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum animal encontrado</p>';
                    }
                    break;
                    
                case 'summary':
                    let summaryHtml = '<div class="space-y-6">';
                    
                    // Produção
                    if (data.production) {
                        summaryHtml += '<div class="bg-blue-50 p-4 rounded-lg"><h3 class="font-bold text-gray-900 mb-3">Produção de Leite</h3>';
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Volume Total:</strong> ${parseFloat(data.production.total_volume || 0).toFixed(2)} L</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Média por Animal:</strong> ${parseFloat(data.production.avg_volume || 0).toFixed(2)} L</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Animais em Produção:</strong> ${data.production.animals_count || 0}</p>`;
                        summaryHtml += '</div>';
                    }
                    
                    // Animais
                    if (data.animals) {
                        summaryHtml += '<div class="bg-green-50 p-4 rounded-lg"><h3 class="font-bold text-gray-900 mb-3">Rebanho</h3>';
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Total de Animais:</strong> ${data.animals.total || 0}</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Lactantes:</strong> ${data.animals.lactating || 0}</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Secas:</strong> ${data.animals.dry || 0}</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Doentes:</strong> ${data.animals.sick || 0}</p>`;
                        summaryHtml += '</div>';
                    }
                    
                    // Saúde
                    if (data.health) {
                        summaryHtml += '<div class="bg-red-50 p-4 rounded-lg"><h3 class="font-bold text-gray-900 mb-3">Saúde</h3>';
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Total de Registros:</strong> ${data.health.total_records || 0}</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Custo Total:</strong> R$ ${parseFloat(data.health.total_cost || 0).toFixed(2)}</p>`;
                        summaryHtml += '</div>';
                    }
                    
                    // Reprodutivo
                    if (data.reproduction) {
                        summaryHtml += '<div class="bg-yellow-50 p-4 rounded-lg"><h3 class="font-bold text-gray-900 mb-3">Reprodutivo</h3>';
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Total de Inseminações:</strong> ${data.reproduction.total_inseminations || 0}</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Resultados Positivos:</strong> ${data.reproduction.positive_results || 0}</p>`;
                        summaryHtml += '</div>';
                    }
                    
                    // Alimentação
                    if (data.feeding) {
                        summaryHtml += '<div class="bg-purple-50 p-4 rounded-lg"><h3 class="font-bold text-gray-900 mb-3">Alimentação</h3>';
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Custo Total:</strong> R$ ${parseFloat(data.feeding.total_cost || 0).toFixed(2)}</p>`;
                        summaryHtml += `<p class="text-sm text-gray-700"><strong>Animais Alimentados:</strong> ${data.feeding.animals_fed || 0}</p>`;
                        summaryHtml += '</div>';
                    }
                    
                    summaryHtml += '</div>';
                    contentDiv.innerHTML = summaryHtml;
                    break;
                    
                case 'feeding':
                    if (data.lots && data.lots.length > 0) {
                        let html = '<div class="mb-4"><h3 class="font-bold text-gray-900 mb-2">Resumo</h3>';
                        if (data.summary) {
                            html += `<p class="text-sm text-gray-700"><strong>Total de Lotes:</strong> ${data.summary.lots_count || 0}</p>`;
                            html += `<p class="text-sm text-gray-700"><strong>Custo Total:</strong> R$ ${parseFloat(data.summary.total_cost || 0).toFixed(2)}</p>`;
                        }
                        html += '</div>';
                        html += '<table class="min-w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lote</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nº Animais</th>';
                        html += '<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Peso Médio (kg)</th>';
                        html += '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ideal Concentrado (kg)</th>';
                        html += '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ideal Volumoso (kg)</th>';
                        html += '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ideal Silagem (kg)</th>';
                        html += '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Ideal MS Total (kg)</th>';
                        html += '<th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Custo Total (R$)</th>';
                        html += '</tr></thead><tbody class="bg-white divide-y divide-gray-200">';
                        
                        data.lots.forEach(lot => {
                            html += '<tr>';
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${lot.group_name || 'N/A'}${lot.group_code ? ' (' + lot.group_code + ')' : ''}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${lot.animal_count || 0}</td>`;
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${lot.avg_weight_kg ? parseFloat(lot.avg_weight_kg).toFixed(2) : '-'}</td>`;
                            if (lot.ideal) {
                                html += `<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">${parseFloat(lot.ideal.concentrate_kg || 0).toFixed(2)}</td>`;
                                html += `<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">${parseFloat(lot.ideal.roughage_kg || 0).toFixed(2)}</td>`;
                                html += `<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">${parseFloat(lot.ideal.silage_kg || 0).toFixed(2)}</td>`;
                                html += `<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900 font-semibold">${parseFloat(lot.ideal.ms_total_kg || 0).toFixed(2)}</td>`;
                            } else {
                                html += '<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-400">-</td>';
                                html += '<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-400">-</td>';
                                html += '<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-400">-</td>';
                                html += '<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-400">-</td>';
                            }
                            html += `<td class="px-4 py-3 whitespace-nowrap text-sm text-right text-gray-900">R$ ${parseFloat(lot.total_cost || 0).toFixed(2)}</td>`;
                            html += '</tr>';
                        });
                        
                        html += '</tbody></table>';
                        contentDiv.innerHTML = html;
                    } else {
                        contentDiv.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum registro de lote encontrado para o período selecionado</p>';
                    }
                    break;
                    
                default:
                    contentDiv.innerHTML = '<p class="text-gray-500 text-center py-8">Pré-visualização não disponível para este tipo de relatório</p>';
                    break;
            }
        }

        function closePreview() {
            document.getElementById('report-preview').classList.add('hidden');
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }
    </script>
</body>
</html>
