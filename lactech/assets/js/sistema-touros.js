/**
 * Sistema de Touros - JavaScript
 * Integração frontend-backend para gerenciamento completo de touros
 */

const API_BASE = 'api/bulls.php';

let currentBullId = null;
let bullsData = [];
let filters = {
    search: '',
    breed: '',
    status: ''
};

// ============================================================
// INICIALIZAÇÃO
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    loadStatistics();
    loadBulls();
    setupEventListeners();
});

function setupEventListeners() {
    // Busca em tempo real
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filters.search = e.target.value;
                loadBulls();
            }, 500);
        });
    }
    
    // Filtros
    const filterBreed = document.getElementById('filter-breed');
    const filterStatus = document.getElementById('filter-status');
    
    if (filterBreed) {
        filterBreed.addEventListener('change', function(e) {
            filters.breed = e.target.value;
            loadBulls();
        });
    }
    
    if (filterStatus) {
        filterStatus.addEventListener('change', function(e) {
            filters.status = e.target.value;
            loadBulls();
        });
    }
    
    // Form submit
    const form = document.getElementById('bull-form');
    if (form) {
        form.addEventListener('submit', handleFormSubmit);
    }
}

// ============================================================
// ESTATÍSTICAS
// ============================================================

async function loadStatistics() {
    try {
        const response = await fetch(`${API_BASE}?action=statistics`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const stats = result.data;
            
            document.getElementById('stat-total').textContent = stats.total_bulls || 0;
            document.getElementById('stat-breeding').textContent = stats.breeding_bulls || 0;
            document.getElementById('stat-efficiency').textContent = 
                stats.avg_efficiency ? stats.avg_efficiency.toFixed(1) + '%' : '-';
            document.getElementById('stat-semen').textContent = 
                (stats.semen && stats.semen.total_available) ? stats.semen.total_available : 0;
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
    }
}

// ============================================================
// LISTAGEM DE TOUROS
// ============================================================

async function loadBulls() {
    const container = document.getElementById('bulls-container');
    const loading = document.getElementById('loading');
    const emptyState = document.getElementById('empty-state');
    
    if (container) container.innerHTML = '';
    if (loading) loading.classList.remove('hidden');
    if (emptyState) emptyState.classList.add('hidden');
    
    try {
        const params = new URLSearchParams({
            action: 'list',
            limit: 50,
            offset: 0
        });
        
        if (filters.search) params.append('search', filters.search);
        if (filters.breed) params.append('breed', filters.breed);
        if (filters.status) params.append('status', filters.status);
        
        const response = await fetch(`${API_BASE}?${params.toString()}`);
        const result = await response.json();
        
        if (loading) loading.classList.add('hidden');
        
        if (result.success && result.data && result.data.bulls) {
            bullsData = result.data.bulls;
            
            if (bullsData.length === 0) {
                if (emptyState) emptyState.classList.remove('hidden');
                return;
            }
            
            renderBullsCards(bullsData);
            loadBreedsFilter(bullsData);
        } else {
            if (emptyState) emptyState.classList.remove('hidden');
            showError(result.error || 'Erro ao carregar touros');
        }
    } catch (error) {
        console.error('Erro ao carregar touros:', error);
        if (loading) loading.classList.add('hidden');
        if (emptyState) emptyState.classList.remove('hidden');
        showError('Erro ao carregar touros');
    }
}

function renderBullsCards(bulls) {
    const container = document.getElementById('bulls-container');
    if (!container) return;
    
    container.innerHTML = bulls.map(bull => createBullCard(bull)).join('');
    
    // Adicionar event listeners aos cards
    bulls.forEach((bull, index) => {
        const card = container.children[index];
        if (card) {
            card.addEventListener('click', () => viewBullDetails(bull.id));
        }
    });
}

function createBullCard(bull) {
    const age = bull.age || calcularIdade(bull.birth_date);
    const efficiency = bull.efficiency_rate || 0;
    const statusClass = getStatusClass(bull.status);
    
    return `
        <div class="card bull-card status-${bull.status || 'ativo'}" data-id="${bull.id}">
            <div class="p-6">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">${escapeHtml(bull.name || bull.bull_number)}</h3>
                        <p class="text-sm text-gray-600">${escapeHtml(bull.bull_number)}</p>
                    </div>
                    <span class="badge ${statusClass}">${getStatusLabel(bull.status)}</span>
                </div>
                
                <div class="space-y-2 mb-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span>${escapeHtml(bull.breed)}</span>
                    </div>
                    
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>${age} ${age === 1 ? 'ano' : 'anos'}</span>
                    </div>
                    
                    ${bull.current_weight ? `
                    <div class="flex items-center text-sm text-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>${bull.current_weight} kg</span>
                    </div>
                    ` : ''}
                </div>
                
                <div class="border-t border-gray-200 pt-4 mt-4">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Coberturas</p>
                            <p class="text-sm font-bold text-gray-900">${bull.total_inseminations || 0}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Sucesso</p>
                            <p class="text-sm font-bold text-gray-900">${bull.successful_inseminations || 0}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600 mb-1">Eficiência</p>
                            <p class="text-sm font-bold ${efficiency >= 70 ? 'text-green-600' : efficiency >= 50 ? 'text-yellow-600' : 'text-red-600'}">
                                ${efficiency.toFixed(1)}%
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 flex space-x-2">
                    <button onclick="event.stopPropagation(); editBull(${bull.id})" 
                            class="flex-1 btn btn-secondary text-sm py-2">
                        Editar
                    </button>
                    <button onclick="event.stopPropagation(); viewBullDetails(${bull.id})" 
                            class="flex-1 btn btn-primary text-sm py-2">
                        Detalhes
                    </button>
                </div>
                ${bull.status !== 'falecido' && bull.status !== 'descartado' ? `
                <button onclick="event.stopPropagation(); deleteBull(${bull.id}, '${escapeHtml(bull.name || bull.bull_number)}')" 
                        class="mt-2 w-full btn text-sm py-2" 
                        style="background: #fee2e2; color: #991b1b;">
                    Excluir
                </button>
                ` : ''}
            </div>
        </div>
    `;
}

function loadBreedsFilter(bulls) {
    const breeds = [...new Set(bulls.map(b => b.breed).filter(Boolean))];
    const filterBreed = document.getElementById('filter-breed');
    
    if (filterBreed) {
        const currentValue = filterBreed.value;
        filterBreed.innerHTML = '<option value="">Todas as raças</option>' + 
            breeds.map(b => `<option value="${escapeHtml(b)}">${escapeHtml(b)}</option>`).join('');
        
        if (currentValue) {
            filterBreed.value = currentValue;
        }
    }
}

function resetFilters() {
    filters = { search: '', breed: '', status: '' };
    
    const searchInput = document.getElementById('search-input');
    const filterBreed = document.getElementById('filter-breed');
    const filterStatus = document.getElementById('filter-status');
    
    if (searchInput) searchInput.value = '';
    if (filterBreed) filterBreed.value = '';
    if (filterStatus) filterStatus.value = '';
    
    loadBulls();
}

// ============================================================
// MODAL E FORMULÁRIOS
// ============================================================

function openCreateModal() {
    currentBullId = null;
    document.getElementById('modal-title').textContent = 'Novo Touro';
    document.getElementById('bull-form').reset();
    document.getElementById('bull-id').value = '';
    // Resetar valores padrão
    document.getElementById('bull-status').value = 'ativo';
    document.getElementById('bull-source').value = 'proprio';
    document.getElementById('bull-breeding-active').value = '1';
    document.getElementById('bull-modal').classList.add('active');
}

function closeModal() {
    const modal = document.getElementById('bull-modal');
    if (modal) {
        modal.classList.remove('active');
    }
    currentBullId = null;
}

async function editBull(id) {
    try {
        const response = await fetch(`${API_BASE}?action=get&id=${id}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const bull = result.data;
            currentBullId = bull.id;
            
            document.getElementById('modal-title').textContent = 'Editar Touro';
            document.getElementById('bull-id').value = bull.id;
            document.getElementById('bull-number').value = bull.bull_number || '';
            document.getElementById('bull-name').value = bull.name || '';
            document.getElementById('bull-breed').value = bull.breed || '';
            document.getElementById('bull-birth-date').value = bull.birth_date || '';
            document.getElementById('bull-rfid').value = bull.rfid_code || '';
            document.getElementById('bull-earring').value = bull.earring_number || '';
            document.getElementById('bull-status').value = bull.status || 'ativo';
            document.getElementById('bull-source').value = bull.source || 'proprio';
            document.getElementById('bull-location').value = bull.location || '';
            document.getElementById('bull-breeding-active').value = bull.is_breeding_active !== undefined ? bull.is_breeding_active : '1';
            document.getElementById('bull-weight').value = bull.weight || '';
            document.getElementById('bull-body-score').value = bull.body_score || '';
            
            // Genealogia
            document.getElementById('bull-sire').value = bull.sire || '';
            document.getElementById('bull-dam').value = bull.dam || '';
            document.getElementById('bull-grandsire-father').value = bull.grandsire_father || '';
            document.getElementById('bull-granddam-father').value = bull.granddam_father || '';
            document.getElementById('bull-grandsire-mother').value = bull.grandsire_mother || '';
            document.getElementById('bull-granddam-mother').value = bull.granddam_mother || '';
            
            // Avaliação Genética
            document.getElementById('bull-genetic-code').value = bull.genetic_code || '';
            document.getElementById('bull-genetic-merit').value = bull.genetic_merit || '';
            document.getElementById('bull-milk-index').value = bull.milk_production_index || '';
            document.getElementById('bull-fat-index').value = bull.fat_production_index || '';
            document.getElementById('bull-protein-index').value = bull.protein_production_index || '';
            document.getElementById('bull-fertility-index').value = bull.fertility_index || '';
            document.getElementById('bull-health-index').value = bull.health_index || '';
            document.getElementById('bull-genetic-evaluation').value = bull.genetic_evaluation || '';
            
            // Observações
            document.getElementById('bull-behavior-notes').value = bull.behavior_notes || '';
            document.getElementById('bull-aptitude-notes').value = bull.aptitude_notes || '';
            document.getElementById('bull-notes').value = bull.notes || '';
            
            document.getElementById('bull-modal').classList.add('active');
        } else {
            showError(result.error || 'Erro ao carregar touro');
        }
    } catch (error) {
        console.error('Erro ao editar touro:', error);
        showError('Erro ao carregar touro');
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submit-btn');
    const submitText = document.getElementById('submit-text');
    const submitLoading = document.getElementById('submit-loading');
    
    submitBtn.disabled = true;
    submitText.textContent = 'Salvando...';
    submitLoading.classList.remove('hidden');
    
    try {
        const formData = {
            action: currentBullId ? 'update' : 'create',
            id: currentBullId,
            bull_number: document.getElementById('bull-number').value,
            name: document.getElementById('bull-name').value,
            breed: document.getElementById('bull-breed').value,
            birth_date: document.getElementById('bull-birth-date').value,
            rfid_code: document.getElementById('bull-rfid').value,
            earring_number: document.getElementById('bull-earring').value,
            status: document.getElementById('bull-status').value,
            source: document.getElementById('bull-source').value,
            location: document.getElementById('bull-location').value,
            is_breeding_active: document.getElementById('bull-breeding-active').value,
            weight: document.getElementById('bull-weight').value,
            body_score: document.getElementById('bull-body-score').value,
            // Genealogia
            sire: document.getElementById('bull-sire').value,
            dam: document.getElementById('bull-dam').value,
            grandsire_father: document.getElementById('bull-grandsire-father').value,
            granddam_father: document.getElementById('bull-granddam-father').value,
            grandsire_mother: document.getElementById('bull-grandsire-mother').value,
            granddam_mother: document.getElementById('bull-granddam-mother').value,
            // Avaliação Genética
            genetic_code: document.getElementById('bull-genetic-code').value,
            genetic_merit: document.getElementById('bull-genetic-merit').value,
            milk_production_index: document.getElementById('bull-milk-index').value,
            fat_production_index: document.getElementById('bull-fat-index').value,
            protein_production_index: document.getElementById('bull-protein-index').value,
            fertility_index: document.getElementById('bull-fertility-index').value,
            health_index: document.getElementById('bull-health-index').value,
            genetic_evaluation: document.getElementById('bull-genetic-evaluation').value,
            // Observações
            behavior_notes: document.getElementById('bull-behavior-notes').value,
            aptitude_notes: document.getElementById('bull-aptitude-notes').value,
            notes: document.getElementById('bull-notes').value
        };
        
        const url = API_BASE;
        const method = currentBullId ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.data?.message || 'Touro salvo com sucesso!');
            closeModal();
            loadBulls();
            loadStatistics();
        } else {
            showError(result.error || 'Erro ao salvar touro');
        }
    } catch (error) {
        console.error('Erro ao salvar touro:', error);
        showError('Erro ao salvar touro');
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'Salvar';
        submitLoading.classList.add('hidden');
    }
}

async function viewBullDetails(id) {
    // Redirecionar para página de detalhes
    // Redirecionar para gerente-completo.php (modal será aberto via JavaScript)
    if (window.openBullDetailsModal) {
        window.openBullDetailsModal(id);
    } else {
        window.location.href = 'gerente-completo.php';
    }
}

async function deleteBull(id, name) {
    if (!confirm(`Tem certeza que deseja excluir o touro "${name}"?\n\nEsta ação marcará o touro como descartado.`)) {
        return;
    }
    
    try {
        const response = await fetch(API_BASE, {
            method: 'DELETE',
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
            showSuccess('Touro removido com sucesso!');
            loadBulls();
            loadStatistics();
        } else {
            showError(result.error || 'Erro ao remover touro');
        }
    } catch (error) {
        console.error('Erro ao remover touro:', error);
        showError('Erro ao remover touro');
    }
}

// ============================================================
// UTILITÁRIOS
// ============================================================

function calcularIdade(dataNascimento) {
    if (!dataNascimento) return 0;
    const hoje = new Date();
    const nasc = new Date(dataNascimento);
    let idade = hoje.getFullYear() - nasc.getFullYear();
    const mes = hoje.getMonth() - nasc.getMonth();
    if (mes < 0 || (mes === 0 && hoje.getDate() < nasc.getDate())) {
        idade--;
    }
    return idade;
}

function getStatusClass(status) {
    const classes = {
        'ativo': 'badge-success',
        'em_reproducao': 'badge-warning',
        'reserva': 'badge-info',
        'descartado': 'badge-danger',
        'falecido': 'badge-danger'
    };
    return classes[status] || 'badge-info';
}

function getStatusLabel(status) {
    const labels = {
        'ativo': 'Ativo',
        'em_reproducao': 'Em Reprodução',
        'reserva': 'Reserva',
        'descartado': 'Descartado',
        'falecido': 'Falecido'
    };
    return labels[status] || status;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    showToast(message, 'error');
}

function showSuccess(message) {
    showToast(message, 'success');
}

function showToast(message, type = 'info') {
    // Criar elemento de toast
    const toast = document.createElement('div');
    const bgColor = type === 'error' ? 'bg-red-500' : type === 'success' ? 'bg-green-500' : 'bg-blue-500';
    toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm text-white ${bgColor}`;
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    toast.style.transition = 'all 0.3s ease-out';
    
    const icon = type === 'error' 
        ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>' 
        : type === 'success' 
        ? '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>' 
        : '<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>';
    
    toast.innerHTML = `
        <div class="flex items-center space-x-3">
            ${icon}
            <span class="flex-1">${escapeHtml(message)}</span>
            <button onclick="this.closest('.fixed').remove()" class="ml-2 text-white hover:text-gray-200">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animação de entrada
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 5000);
}

