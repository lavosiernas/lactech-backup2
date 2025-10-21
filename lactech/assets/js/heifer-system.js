/**
 * ============================================
 * SISTEMA COMPLETO DE CONTROLE DE NOVILHAS
 * Gestão desde o nascimento até 26 meses
 * ============================================
 */

// Cache global
let heiferCache = {
    heifers: [],
    phases: [],
    categories: [],
    dashboard: null
};

/**
 * ABRIR DASHBOARD DE NOVILHAS
 */
window.openHeiferManagement = async function() {
    console.log('🐄 Abrindo Sistema de Controle de Novilhas...');
    
    // Carregar dados do dashboard
    await loadHeiferDashboard();
    
    const overlay = document.getElementById('heiferOverlay');
    if (overlay) {
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
    }
};

/**
 * FECHAR OVERLAY
 */
window.closeHeiferOverlay = function() {
    const overlay = document.getElementById('heiferOverlay');
    if (overlay) {
        overlay.classList.remove('flex');
        overlay.classList.add('hidden');
    }
};

/**
 * CARREGAR DASHBOARD
 */
async function loadHeiferDashboard() {
    console.log('📊 Carregando dashboard...');
    
    try {
        const response = await fetch('api/heifer_management.php?action=get_dashboard');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Erro ao carregar dashboard');
        }
        
        heiferCache.dashboard = data.data;
        
        // Atualizar estatísticas
        updateDashboardStats(data.data.statistics);
        
        // Atualizar gráfico de custos por categoria
        updateCostsByCategoryChart(data.data.costs_by_category);
        
        // Atualizar lista de novilhas mais caras
        updateTopExpensiveList(data.data.top_expensive_heifers);
        
        // Carregar lista completa de novilhas
        await loadHeifersList();
        
    } catch (error) {
        console.error('❌ Erro ao carregar dashboard:', error);
        showErrorMessage(error.message);
    }
}

/**
 * ATUALIZAR ESTATÍSTICAS DO DASHBOARD
 */
function updateDashboardStats(stats) {
    console.log('📈 Atualizando estatísticas:', stats);
    
    // Total de novilhas
    const totalElement = document.getElementById('heiferTotalCount');
    if (totalElement) {
        totalElement.textContent = stats.total_heifers || 0;
    }
    
    // Investimento total
    const investmentElement = document.getElementById('heiferTotalInvestment');
    if (investmentElement) {
        investmentElement.textContent = formatCurrency(stats.total_invested || 0);
    }
    
    // Custo médio
    const avgCostElement = document.getElementById('heiferAvgCost');
    if (avgCostElement) {
        avgCostElement.textContent = formatCurrency(stats.avg_cost_per_record || 0);
    }
    
    // Novilhas por fase
    const phasesData = [
        { name: 'Aleitamento', count: stats.phase_aleitamento || 0 },
        { name: 'Transição', count: stats.phase_transicao || 0 },
        { name: 'Recria Inicial', count: stats.phase_recria1 || 0 },
        { name: 'Recria Inter.', count: stats.phase_recria2 || 0 },
        { name: 'Crescimento', count: stats.phase_crescimento || 0 },
        { name: 'Pré-parto', count: stats.phase_preparto || 0 }
    ];
    
    const phasesContainer = document.getElementById('heiferPhasesList');
    if (phasesContainer) {
        phasesContainer.innerHTML = phasesData.map(phase => `
            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                <span class="text-sm text-gray-700">${phase.name}</span>
                <span class="text-sm font-bold text-green-600">${phase.count}</span>
            </div>
        `).join('');
    }
}

/**
 * ATUALIZAR GRÁFICO DE CUSTOS POR CATEGORIA
 */
function updateCostsByCategoryChart(costs) {
    console.log('📊 Atualizando gráfico de custos:', costs);
    
    const chartContainer = document.getElementById('costsByCategoryChart');
    if (!chartContainer) return;
    
    const total = costs.reduce((sum, item) => sum + parseFloat(item.total_cost || 0), 0);
    
    if (total === 0) {
        chartContainer.innerHTML = '<p class="text-center text-gray-500 text-sm py-4">Nenhum custo registrado ainda</p>';
        return;
    }
    
    const colors = {
        'Alimentação': '#10B981',
        'Mão de Obra': '#3B82F6',
        'Sanidade': '#EF4444',
        'Manejo': '#F59E0B',
        'Instalações': '#8B5CF6',
        'Outros': '#6B7280'
    };
    
    chartContainer.innerHTML = costs.map(item => {
        const percentage = (parseFloat(item.total_cost) / total * 100).toFixed(1);
        const color = colors[item.category_type] || '#6B7280';
        
        return `
            <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-gray-700">${item.category_type}</span>
                    <span class="text-xs font-bold" style="color: ${color}">${formatCurrency(item.total_cost)}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full" style="width: ${percentage}%; background-color: ${color}"></div>
                </div>
                <span class="text-xs text-gray-500">${percentage}% do total</span>
            </div>
        `;
    }).join('');
}

/**
 * ATUALIZAR LISTA DE NOVILHAS MAIS CARAS
 */
function updateTopExpensiveList(heifers) {
    console.log('💰 Atualizando top 10 mais caras:', heifers);
    
    const listContainer = document.getElementById('topExpensiveList');
    if (!listContainer) return;
    
    if (!heifers || heifers.length === 0) {
        listContainer.innerHTML = '<p class="text-center text-gray-500 text-sm py-4">Nenhuma novilha com custos registrados</p>';
        return;
    }
    
    listContainer.innerHTML = heifers.map((heifer, index) => `
        <div class="flex items-center justify-between py-2 border-b border-gray-100 hover:bg-gray-50 cursor-pointer" onclick="viewHeiferDetails(${heifer.id})">
            <div class="flex items-center space-x-3">
                <span class="text-xs font-bold text-gray-400">#${index + 1}</span>
                <div>
                    <p class="text-sm font-medium text-gray-900">${heifer.ear_tag}</p>
                    <p class="text-xs text-gray-500">${heifer.name || 'Sem nome'} · ${heifer.age_months} meses</p>
                </div>
            </div>
            <span class="text-sm font-bold text-red-600">${formatCurrency(heifer.total_cost)}</span>
        </div>
    `).join('');
}

/**
 * CARREGAR LISTA DE NOVILHAS
 */
async function loadHeifersList() {
    console.log('📋 Carregando lista de novilhas...');
    
    try {
        const response = await fetch('api/heifer_management.php?action=get_heifers_list');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Erro ao carregar novilhas');
        }
        
        heiferCache.heifers = data.data;
        renderHeifersTable(data.data);
        
    } catch (error) {
        console.error('❌ Erro ao carregar novilhas:', error);
        showErrorMessage(error.message);
    }
}

/**
 * RENDERIZAR TABELA DE NOVILHAS
 */
function renderHeifersTable(heifers) {
    console.log('🖼️ Renderizando tabela:', heifers.length, 'novilhas');
    
    const tbody = document.getElementById('heifersTableBody');
    if (!tbody) return;
    
    if (!heifers || heifers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                    <div class="flex flex-col items-center">
                        <svg class="w-12 h-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                        <p>Nenhuma novilha encontrada</p>
                        <p class="text-sm mt-1">Cadastre animais na categoria "Novilha" ou "Bezerro"</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = heifers.map(heifer => {
        const phaseColor = getPhaseColor(heifer.current_phase);
        
        return `
            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">${heifer.ear_tag}</td>
                <td class="px-4 py-3 text-sm text-gray-600">${heifer.name || '-'}</td>
                <td class="px-4 py-3 text-sm text-gray-600">${heifer.age_months} meses (${heifer.age_days} dias)</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs font-medium rounded-full" style="background-color: ${phaseColor}20; color: ${phaseColor}">
                        ${heifer.current_phase || 'Não definida'}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm font-bold text-green-600">${formatCurrency(heifer.total_cost)}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${heifer.total_records} registros</td>
                <td class="px-4 py-3 text-sm text-gray-500">${heifer.last_cost_date ? formatDate(heifer.last_cost_date) : '-'}</td>
                <td class="px-4 py-3">
                    <div class="flex items-center space-x-2">
                        <button onclick="viewHeiferDetails(${heifer.id})" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="Ver Detalhes">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>
                        <button onclick="showAddCostForHeifer(${heifer.id})" class="p-1 text-green-600 hover:bg-green-50 rounded" title="Adicionar Custo">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </button>
                        <button onclick="generateHeiferReport(${heifer.id})" class="p-1 text-purple-600 hover:bg-purple-50 rounded" title="Gerar Relatório">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * VER DETALHES DA NOVILHA
 */
window.viewHeiferDetails = async function(animalId) {
    console.log('🔍 Visualizando detalhes da novilha:', animalId);
    
    try {
        const response = await fetch(`api/heifer_management.php?action=get_heifer_details&animal_id=${animalId}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Erro ao carregar detalhes');
        }
        
        showHeiferDetailsModal(data.data);
        
    } catch (error) {
        console.error('❌ Erro ao carregar detalhes:', error);
        showErrorMessage(error.message);
    }
};

/**
 * MOSTRAR MODAL DE DETALHES
 */
function showHeiferDetailsModal(heiferData) {
    const { animal, total_cost, total_records, avg_daily_cost, costs_by_category, costs_by_phase, recent_costs } = heiferData;
    
    const modal = document.createElement('div');
    modal.id = 'heiferDetailsModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
    
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-white">${animal.ear_tag} - ${animal.name || 'Sem nome'}</h3>
                    <p class="text-white text-opacity-90 text-sm">${animal.age_months} meses · ${animal.current_phase}</p>
                </div>
                <button onclick="closeHeiferDetailsModal()" class="text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Resumo Financeiro -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                        <p class="text-xs font-medium text-blue-600 mb-1">Custo Total</p>
                        <p class="text-2xl font-bold text-blue-900">${formatCurrency(total_cost)}</p>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                        <p class="text-xs font-medium text-green-600 mb-1">Custo Médio Diário</p>
                        <p class="text-2xl font-bold text-green-900">${formatCurrency(avg_daily_cost)}</p>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                        <p class="text-xs font-medium text-purple-600 mb-1">Total de Registros</p>
                        <p class="text-2xl font-bold text-purple-900">${total_records}</p>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 p-4 rounded-lg border border-orange-200">
                        <p class="text-xs font-medium text-orange-600 mb-1">Idade</p>
                        <p class="text-2xl font-bold text-orange-900">${animal.age_days} dias</p>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-4">
                    <div class="flex space-x-4">
                        <button onclick="switchHeiferTab('category')" id="tabCategory" class="heifer-tab-btn px-4 py-2 font-medium text-sm border-b-2 border-green-600 text-green-600">
                            Por Categoria
                        </button>
                        <button onclick="switchHeiferTab('phase')" id="tabPhase" class="heifer-tab-btn px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            Por Fase
                        </button>
                        <button onclick="switchHeiferTab('history')" id="tabHistory" class="heifer-tab-btn px-4 py-2 font-medium text-sm border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                            Histórico
                        </button>
                    </div>
                </div>
                
                <!-- Tab Content: Custos por Categoria -->
                <div id="contentCategory" class="heifer-tab-content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        ${costs_by_category.map(cat => `
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-gray-900">${cat.category_type}</h4>
                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded">${cat.total_records} registros</span>
                                </div>
                                <p class="text-2xl font-bold text-green-600">${formatCurrency(cat.total_cost)}</p>
                                <p class="text-xs text-gray-500 mt-1">${((cat.total_cost / total_cost) * 100).toFixed(1)}% do total</p>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Tab Content: Custos por Fase -->
                <div id="contentPhase" class="heifer-tab-content hidden">
                    <div class="space-y-3">
                        ${costs_by_phase.map(phase => `
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">${phase.phase_name || 'Fase não especificada'}</h4>
                                        <p class="text-xs text-gray-500">Dias ${phase.start_day} a ${phase.end_day}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xl font-bold text-green-600">${formatCurrency(phase.phase_total_cost)}</p>
                                        <p class="text-xs text-gray-500">${phase.phase_records} registros</p>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Tab Content: Histórico -->
                <div id="contentHistory" class="heifer-tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Data</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Categoria</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Fase</th>
                                    <th class="px-4 py-2 text-left font-semibold text-gray-700">Descrição</th>
                                    <th class="px-4 py-2 text-right font-semibold text-gray-700">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${recent_costs.map(cost => `
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="px-4 py-2 text-gray-600">${formatDate(cost.cost_date)}</td>
                                        <td class="px-4 py-2">
                                            <span class="text-xs px-2 py-1 bg-gray-100 rounded">${cost.category_name}</span>
                                        </td>
                                        <td class="px-4 py-2 text-gray-600 text-xs">${cost.phase_name || '-'}</td>
                                        <td class="px-4 py-2 text-gray-600">${cost.description || '-'}</td>
                                        <td class="px-4 py-2 text-right font-semibold text-green-600">${formatCurrency(cost.total_cost)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="border-t border-gray-200 px-6 py-4 flex items-center justify-between bg-gray-50">
                <button onclick="showAddCostForHeifer(${animal.id})" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span>Adicionar Custo</span>
                </button>
                <div class="flex items-center space-x-2">
                    <button onclick="generateHeiferReport(${animal.id})" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium text-sm">
                        Gerar Relatório PDF
                    </button>
                    <button onclick="closeHeiferDetailsModal()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium text-sm">
                        Fechar
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) closeHeiferDetailsModal(); });
}

window.closeHeiferDetailsModal = function() {
    const modal = document.getElementById('heiferDetailsModal');
    if (modal) modal.remove();
};

window.switchHeiferTab = function(tab) {
    // Atualizar botões
    document.querySelectorAll('.heifer-tab-btn').forEach(btn => {
        btn.classList.remove('border-green-600', 'text-green-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    const activeBtn = document.getElementById(`tab${tab.charAt(0).toUpperCase() + tab.slice(1)}`);
    if (activeBtn) {
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
        activeBtn.classList.add('border-green-600', 'text-green-600');
    }
    
    // Atualizar conteúdo
    document.querySelectorAll('.heifer-tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    const activeContent = document.getElementById(`content${tab.charAt(0).toUpperCase() + tab.slice(1)}`);
    if (activeContent) {
        activeContent.classList.remove('hidden');
    }
};

/**
 * ADICIONAR CUSTO PARA NOVILHA ESPECÍFICA
 */
window.showAddCostForHeifer = async function(animalId) {
    console.log('💰 Abrindo formulário de custo para novilha:', animalId);
    
    // Fechar modal de detalhes se estiver aberto
    closeHeiferDetailsModal();
    
    // Carregar categorias e fases se necessário
    if (heiferCache.categories.length === 0) {
        const response = await fetch('api/heifer_management.php?action=get_cost_categories');
        const data = await response.json();
        if (data.success) heiferCache.categories = data.data;
    }
    
    if (heiferCache.phases.length === 0) {
        const response = await fetch('api/heifer_management.php?action=get_phases');
        const data = await response.json();
        if (data.success) heiferCache.phases = data.data;
    }
    
    // Buscar informações do animal
    const animalResponse = await fetch(`api/heifer_management.php?action=get_heifer_details&animal_id=${animalId}`);
    const animalData = await animalResponse.json();
    const animal = animalData.data.animal;
    
    showAddCostModal(animal);
};

/**
 * MOSTRAR MODAL DE ADICIONAR CUSTO
 */
function showAddCostModal(preSelectedAnimal = null) {
    const modal = document.createElement('div');
    modal.id = 'addHeiferCostModalNew';
    modal.className = 'fixed inset-0 bg-black bg-opacity-60 z-[999999] flex items-center justify-center p-4';
    
    // Agrupar categorias por tipo
    const groupedCategories = {};
    heiferCache.categories.forEach(cat => {
        if (!groupedCategories[cat.category_type]) {
            groupedCategories[cat.category_type] = [];
        }
        groupedCategories[cat.category_type].push(cat);
    });
    
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-white">
                    ${preSelectedAnimal ? `Adicionar Custo - ${preSelectedAnimal.ear_tag}` : 'Adicionar Custo de Criação'}
                </h3>
                <button onclick="closeAddHeiferCostModalNew()" class="text-white hover:bg-white hover:bg-opacity-20 p-2 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="addHeiferCostFormNew" class="flex-1 overflow-y-auto p-6">
                <div class="space-y-4">
                    ${!preSelectedAnimal ? `
                        <!-- Seleção de Novilha -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Novilha *</label>
                            <select name="animal_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="">Selecione uma novilha...</option>
                                ${heiferCache.heifers.map(h => `
                                    <option value="${h.id}">${h.ear_tag} - ${h.name || 'Sem nome'} (${h.age_months} meses)</option>
                                `).join('')}
                            </select>
                        </div>
                    ` : `
                        <input type="hidden" name="animal_id" value="${preSelectedAnimal.id}">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <p class="text-sm font-medium text-green-900">Novilha: ${preSelectedAnimal.ear_tag}</p>
                            <p class="text-xs text-green-700 mt-1">${preSelectedAnimal.name || 'Sem nome'} · ${preSelectedAnimal.age_months} meses · ${preSelectedAnimal.current_phase}</p>
                        </div>
                    `}
                    
                    <!-- Data do Custo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Data do Custo *</label>
                        <input type="date" name="cost_date" required value="${new Date().toISOString().split('T')[0]}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                    </div>
                    
                    <!-- Categoria -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Categoria de Custo *</label>
                        <select name="category_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="">Selecione uma categoria...</option>
                            ${Object.keys(groupedCategories).map(type => `
                                <optgroup label="${type}">
                                    ${groupedCategories[type].map(cat => `
                                        <option value="${cat.id}">${cat.category_name}</option>
                                    `).join('')}
                                </optgroup>
                            `).join('')}
                        </select>
                    </div>
                    
                    <!-- Quantidade e Unidade -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Quantidade</label>
                            <input type="number" name="quantity" step="0.001" value="1" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Unidade</label>
                            <select name="unit" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                                <option value="Unidade">Unidade</option>
                                <option value="Litros">Litros</option>
                                <option value="Kg">Kg</option>
                                <option value="Dias">Dias</option>
                                <option value="Hora">Hora</option>
                                <option value="Mês">Mês</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Preço Unitário -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Preço Unitário (R$) *</label>
                        <input type="number" name="unit_price" required step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" placeholder="Ex: 15.50">
                        <p class="text-xs text-gray-500 mt-1">O valor total será calculado automaticamente (quantidade × preço unitário)</p>
                    </div>
                    
                    <!-- Descrição -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 resize-none" placeholder="Descreva detalhadamente este custo..."></textarea>
                    </div>
                </div>
            </form>
            
            <div class="border-t border-gray-200 px-6 py-4 flex items-center justify-end space-x-3 bg-gray-50">
                <button onclick="closeAddHeiferCostModalNew()" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium text-sm">
                    Cancelar
                </button>
                <button onclick="submitAddHeiferCostNew()" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Registrar Custo</span>
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.addEventListener('click', e => { if (e.target === modal) closeAddHeiferCostModalNew(); });
}

window.closeAddHeiferCostModalNew = function() {
    const modal = document.getElementById('addHeiferCostModalNew');
    if (modal) modal.remove();
};

window.submitAddHeiferCostNew = async function() {
    const form = document.getElementById('addHeiferCostFormNew');
    if (!form || !form.checkValidity()) {
        form?.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const costData = {
        animal_id: formData.get('animal_id'),
        cost_date: formData.get('cost_date'),
        category_id: formData.get('category_id'),
        quantity: formData.get('quantity') || 1,
        unit: formData.get('unit') || 'Unidade',
        unit_price: formData.get('unit_price'),
        description: formData.get('description') || null
    };
    
    console.log('📤 Enviando custo:', costData);
    
    try {
        const response = await fetch('api/heifer_management.php?action=add_cost', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(costData)
        });
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Erro ao adicionar custo');
        }
        
        showSuccessMessage('✅ Custo registrado com sucesso!');
        closeAddHeiferCostModalNew();
        await loadHeiferDashboard();
        
    } catch (error) {
        console.error('❌ Erro ao adicionar custo:', error);
        showErrorMessage(error.message);
    }
};

/**
 * GERAR RELATÓRIO PDF
 */
window.generateHeiferReport = async function(animalId) {
    console.log('📄 Gerando relatório para novilha:', animalId);
    showSuccessMessage('Funcionalidade em desenvolvimento');
};

/**
 * FUNÇÕES AUXILIARES
 */
function getPhaseColor(phaseName) {
    const colors = {
        'Aleitamento': '#EF4444',
        'Transição/Desmame': '#F59E0B',
        'Recria Inicial': '#10B981',
        'Recria Intermediária': '#3B82F6',
        'Crescimento/Desenvolvimento': '#8B5CF6',
        'Pré-parto': '#EC4899'
    };
    return colors[phaseName] || '#6B7280';
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value || 0);
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('pt-BR');
}

function showSuccessMessage(message) {
    // Implementar notificação de sucesso
    console.log('✅', message);
    alert(message);
}

function showErrorMessage(message) {
    // Implementar notificação de erro
    console.error('❌', message);
    alert('Erro: ' + message);
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🐄 Sistema de Controle de Novilhas carregado!');
});

