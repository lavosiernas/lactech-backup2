/**
 * Sistema de Touros - Página de Detalhes
 * JavaScript completo para gerenciar todas as funcionalidades do touro
 */

const API_BASE = 'api/bulls.php';
let bullData = null;
let currentTab = 'info';
let weightChart = null;
let reproductionChart = null;
let efficiencyChart = null;

// ============================================================
// INICIALIZAÇÃO
// ============================================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Carregado - Inicializando página de detalhes do touro');
    console.log('BULL_ID:', typeof BULL_ID !== 'undefined' ? BULL_ID : 'NÃO DEFINIDO');
    console.log('API_BASE:', typeof API_BASE !== 'undefined' ? API_BASE : 'NÃO DEFINIDO');
    
    // Verificar se as constantes estão definidas
    if (typeof BULL_ID === 'undefined' || !BULL_ID || BULL_ID <= 0) {
        console.error('BULL_ID não está definido ou é inválido');
        showError('ID do touro não encontrado. Por favor, acesse através da lista de touros.');
        return;
    }
    
    if (typeof API_BASE === 'undefined') {
        console.error('API_BASE não está definido');
        showError('Erro de configuração do sistema');
        return;
    }
    
    // Inicializar página
    try {
        loadBullData();
        setupEventListeners();
        loadCows(); // Carregar lista de vacas para o modal de coberturas
        
        // Configurar event listeners dos formulários
        const coatingForm = document.getElementById('coating-form');
        if (coatingForm) {
            coatingForm.addEventListener('submit', handleCoatingSubmit);
            console.log('Formulário de cobertura configurado');
        }
        
        const semenForm = document.getElementById('semen-form');
        if (semenForm) {
            semenForm.addEventListener('submit', handleSemenSubmit);
            console.log('Formulário de sêmen configurado');
        }
        
        const healthForm = document.getElementById('health-form');
        if (healthForm) {
            healthForm.addEventListener('submit', handleHealthSubmit);
            console.log('Formulário de saúde configurado');
        }
        
        const weightForm = document.getElementById('weight-form');
        if (weightForm) {
            weightForm.addEventListener('submit', handleWeightSubmit);
            console.log('Formulário de peso configurado');
        }
        
        const documentForm = document.getElementById('document-form');
        if (documentForm) {
            documentForm.addEventListener('submit', handleDocumentSubmit);
            console.log('Formulário de documento configurado');
        }
        
        console.log('Inicialização concluída');
    } catch (error) {
        console.error('Erro na inicialização:', error);
        showError('Erro ao inicializar página: ' + error.message);
    }
});

function setupEventListeners() {
    // Fechar modal ao clicar fora
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });
}

// ============================================================
// CARREGAMENTO DE DADOS
// ============================================================

async function loadBullData() {
    try {
        console.log('Carregando dados do touro ID:', BULL_ID);
        
        if (!BULL_ID || BULL_ID <= 0) {
            console.error('BULL_ID inválido:', BULL_ID);
            showError('ID do touro inválido');
            return;
        }
        
        const url = `${API_BASE}?action=get&id=${BULL_ID}`;
        console.log('URL da requisição:', url);
        
        const response = await fetch(url);
        console.log('Status da resposta:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Erro HTTP:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Resultado da API:', result);
        
        // A API retorna { success: true, data: {...}, timestamp: "..." }
        if (result.success && result.data) {
            bullData = result.data;
            console.log('Dados do touro carregados:', bullData);
            renderBullInfo(bullData);
            loadTabContent(currentTab);
        } else if (result.error) {
            // Erro da API
            console.error('Erro na resposta da API:', result);
            showError(result.error || 'Erro ao carregar dados do touro');
        } else {
            // Tentar usar resultado direto se não tiver wrapper
            console.warn('Resposta sem formato esperado, tentando usar resultado direto');
            bullData = result;
            renderBullInfo(bullData);
            loadTabContent(currentTab);
        }
    } catch (error) {
        console.error('Erro ao carregar dados:', error);
        showError('Erro ao carregar dados do touro: ' + error.message);
    }
}

function renderBullInfo(data) {
    console.log('Renderizando informações do touro:', data);
    
    try {
        // Header - Mostrar nome do touro
        const headerEl = document.getElementById('bull-name-header');
        if (headerEl) {
            const displayName = data.name || data.bull_number || 'Sem nome';
            headerEl.textContent = displayName;
        }
        
        // Informações básicas
        setElementText('bull-number', data.bull_number || '-');
        setElementText('bull-breed', data.breed || '-');
        setElementText('bull-age', data.age ? data.age + ' anos' : '-');
        setElementText('bull-weight', data.current_weight ? data.current_weight + ' kg' : data.weight ? data.weight + ' kg' : '-');
        
        // Status
        const statusBadge = document.getElementById('bull-status-badge');
        if (statusBadge) {
            statusBadge.textContent = getStatusLabel(data.status || 'ativo');
            statusBadge.className = 'badge ' + getStatusClass(data.status || 'ativo');
        }
        
        // Eficiência
        const totalServices = (parseInt(data.total_inseminations) || 0) + (parseInt(data.total_coatings) || 0);
        const totalSuccessful = (parseInt(data.successful_inseminations) || 0) + (parseInt(data.successful_coatings) || 0);
        const efficiency = totalServices > 0 ? ((totalSuccessful / totalServices) * 100).toFixed(1) : 0;
        setElementText('bull-efficiency', efficiency + '%');
        
        // Aba Informações
        setElementText('info-name', data.name || '-');
        setElementText('info-rfid', data.rfid_code || '-');
        setElementText('info-earring', data.earring_number || '-');
        setElementText('info-birth-date', data.birth_date ? formatDate(data.birth_date) : '-');
        setElementText('info-source', getSourceLabel(data.source));
        setElementText('info-location', data.location || '-');
        
        // Genealogia
        setElementText('info-sire', data.sire || '-');
        setElementText('info-dam', data.dam || '-');
        setElementText('info-grandsire-father', data.grandsire_father || '-');
        setElementText('info-granddam-father', data.granddam_father || '-');
        
        // Avaliação Genética
        setElementText('info-genetic-merit', data.genetic_merit || '-');
        setElementText('info-milk-index', data.milk_production_index || '-');
        setElementText('info-fat-index', data.fat_production_index || '-');
        setElementText('info-protein-index', data.protein_production_index || '-');
        
        console.log('Informações renderizadas com sucesso');
    } catch (error) {
        console.error('Erro ao renderizar informações:', error);
        showError('Erro ao exibir dados do touro');
    }
}

function setElementText(id, text) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = text;
    } else {
        console.warn('Elemento não encontrado:', id);
    }
}

// ============================================================
// SISTEMA DE ABAS
// ============================================================

// Função global para trocar de aba
window.switchTab = function(tabName) {
    console.log('Trocando para aba:', tabName);
    
    try {
        // Remover active de todas as abas
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Ativar aba selecionada
        const button = document.querySelector(`[data-tab="${tabName}"]`);
        const content = document.getElementById(`tab-${tabName}`);
        
        if (button) {
            button.classList.add('active');
            console.log('Botão da aba ativado:', tabName);
        } else {
            console.warn('Botão da aba não encontrado:', tabName);
        }
        
        if (content) {
            content.classList.add('active');
            console.log('Conteúdo da aba ativado:', tabName);
        } else {
            console.warn('Conteúdo da aba não encontrado:', tabName);
        }
        
        currentTab = tabName;
        loadTabContent(tabName);
    } catch (error) {
        console.error('Erro ao trocar de aba:', error);
        showError('Erro ao trocar de aba: ' + error.message);
    }
};

async function loadTabContent(tabName) {
    console.log('Carregando conteúdo da aba:', tabName);
    
    try {
        switch(tabName) {
            case 'coatings':
                await loadCoatings();
                break;
            case 'semen':
                await loadSemen();
                break;
            case 'health':
                await loadHealthRecords();
                break;
            case 'weight':
                await loadWeightHistory();
                break;
            case 'documents':
                await loadDocuments();
                break;
            case 'offspring':
                await loadOffspring();
                break;
            case 'reports':
                await loadReports();
                break;
            default:
                console.log('Aba sem conteúdo específico:', tabName);
        }
    } catch (error) {
        console.error('Erro ao carregar conteúdo da aba:', tabName, error);
        showError('Erro ao carregar conteúdo da aba');
    }
}

// ============================================================
// COBERTURAS NATURAIS
// ============================================================

async function loadCoatings() {
    try {
        console.log('Carregando coberturas para touro:', BULL_ID);
        const response = await fetch(`${API_BASE}?action=coatings_list&bull_id=${BULL_ID}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Resultado coberturas:', result);
        
        if (result.success && result.data) {
            renderCoatingsTable(result.data.coatings || []);
        } else {
            console.warn('Resposta sem dados de coberturas:', result);
            renderCoatingsTable([]);
        }
    } catch (error) {
        console.error('Erro ao carregar coberturas:', error);
        renderCoatingsTable([]);
    }
}

function renderCoatingsTable(coatings) {
    const tbody = document.getElementById('coatings-table-body');
    
    if (!tbody) {
        console.warn('Elemento coatings-table-body não encontrado');
        return;
    }
    
    if (!coatings || coatings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500"></td></tr>';
        return;
    }
    
    console.log('Renderizando', coatings.length, 'coberturas');
    
    tbody.innerHTML = coatings.map(coating => `
        <tr>
            <td>${formatDate(coating.coating_date)}</td>
            <td>${escapeHtml(coating.cow_name || coating.animal_number || '-')}</td>
            <td>${getCoatingTypeLabel(coating.coating_type || 'natural')}</td>
            <td><span class="badge ${getResultClass(coating.result || 'pendente')}">${getResultLabel(coating.result || 'pendente')}</span></td>
            <td>${escapeHtml(coating.technician_name || '-')}</td>
            <td>
                <button onclick="editCoating(${coating.id})" class="btn btn-secondary text-sm">Editar</button>
            </td>
        </tr>
    `).join('');
}

let currentCoatingId = null;
let cowsList = [];

async function loadCows() {
    try {
        const response = await fetch(`${ANIMALS_API}?action=get_all`);
        const result = await response.json();
        
        let animals = [];
        if (result && Array.isArray(result)) {
            animals = result;
        } else if (result.success && result.data && Array.isArray(result.data)) {
            animals = result.data;
        } else if (result.data && Array.isArray(result.data)) {
            animals = result.data;
        }
        
        // Filtrar apenas fêmeas (vacas)
        cowsList = animals.filter(a => {
            const gender = (a.gender || '').toLowerCase();
            return gender === 'femea' || gender === 'fêmea' || gender === 'female';
        });
        
        // Preencher select de vacas no modal
        const select = document.getElementById('coating-cow-id');
        if (select) {
            select.innerHTML = '<option value="">Selecione uma vaca</option>' +
                cowsList.map(cow => `<option value="${cow.id}">${cow.animal_number || ''}${cow.name ? ' - ' + cow.name : ''}</option>`).join('');
        }
    } catch (error) {
        console.error('Erro ao carregar vacas:', error);
    }
}

window.openCoatingModal = function(id = null) {
    currentCoatingId = id;
    const modal = document.getElementById('modal-coating');
    const form = document.getElementById('coating-form');
    const title = document.getElementById('coating-modal-title');
    
    if (!modal || !form) return;
    
    if (id) {
        title.textContent = 'Editar Cobertura';
        // Carregar dados da cobertura
        loadCoatingData(id);
    } else {
        title.textContent = 'Nova Cobertura';
        form.reset();
        document.getElementById('coating-id').value = '';
        document.getElementById('coating-date').value = new Date().toISOString().split('T')[0];
        document.getElementById('coating-result').value = 'pendente';
    }
    
    if (cowsList.length === 0) {
        loadCows();
    }
    
    modal.classList.add('active');
};

async function loadCoatingData(id) {
    try {
        const response = await fetch(`${API_BASE}?action=coatings_list&bull_id=${BULL_ID}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.coatings) {
            const coating = result.data.coatings.find(c => c.id === id);
            if (coating) {
                document.getElementById('coating-id').value = coating.id;
                document.getElementById('coating-date').value = coating.coating_date || '';
                document.getElementById('coating-time').value = coating.coating_time || '';
                document.getElementById('coating-cow-id').value = coating.cow_id || '';
                document.getElementById('coating-type').value = coating.coating_type || 'natural';
                document.getElementById('coating-result').value = coating.result || 'pendente';
                document.getElementById('coating-check-date').value = coating.pregnancy_check_date || '';
                document.getElementById('coating-technician').value = coating.technician_name || '';
                document.getElementById('coating-notes').value = coating.notes || '';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar cobertura:', error);
    }
}

async function handleCoatingSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('coating-submit-btn');
    const submitText = document.getElementById('coating-submit-text');
    const submitLoading = document.getElementById('coating-submit-loading');
    
    submitBtn.disabled = true;
    submitText.textContent = 'Salvando...';
    submitLoading.classList.remove('hidden');
    
    try {
        const formData = {
            action: currentCoatingId ? 'coating_update' : 'coating_create',
            id: currentCoatingId,
            bull_id: BULL_ID,
            cow_id: document.getElementById('coating-cow-id').value,
            coating_date: document.getElementById('coating-date').value,
            coating_time: document.getElementById('coating-time').value,
            coating_type: document.getElementById('coating-type').value,
            result: document.getElementById('coating-result').value,
            pregnancy_check_date: document.getElementById('coating-check-date').value,
            technician_name: document.getElementById('coating-technician').value,
            notes: document.getElementById('coating-notes').value
        };
        
        const method = currentCoatingId ? 'PUT' : 'POST';
        const response = await fetch(API_BASE, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.data?.message || 'Cobertura salva com sucesso!');
            closeModal('modal-coating');
            loadCoatings();
            loadBullData(); // Atualizar estatísticas
        } else {
            showError(result.error || 'Erro ao salvar cobertura');
        }
    } catch (error) {
        console.error('Erro ao salvar cobertura:', error);
        showError('Erro ao salvar cobertura');
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'Salvar';
        submitLoading.classList.add('hidden');
    }
}

window.editCoating = function(id) {
    openCoatingModal(id);
};

// Event listeners serão configurados na inicialização

// ============================================================
// GESTÃO DE SÊMEN
// ============================================================

async function loadSemen() {
    try {
        console.log('Carregando sêmen para touro:', BULL_ID);
        const response = await fetch(`${API_BASE}?action=semen_list&bull_id=${BULL_ID}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Resultado sêmen:', result);
        
        if (result.success && result.data) {
            renderSemenTable(result.data.semen || []);
        } else {
            console.warn('Resposta sem dados de sêmen:', result);
            renderSemenTable([]);
        }
    } catch (error) {
        console.error('Erro ao carregar sêmen:', error);
        renderSemenTable([]);
    }
}

function renderSemenTable(semen) {
    const tbody = document.getElementById('semen-table-body');
    
    if (!tbody) {
        console.warn('Elemento semen-table-body não encontrado');
        return;
    }
    
    if (!semen || semen.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-8 text-gray-500"></td></tr>';
        return;
    }
    
    console.log('Renderizando', semen.length, 'lotes de sêmen');
    
    tbody.innerHTML = semen.map(s => `
        <tr>
            <td>${escapeHtml(s.batch_number || '-')}</td>
            <td>${formatDate(s.collection_date || s.production_date)}</td>
            <td>
                <span class="${getValidityClass(s.validity_status || 'valido')}">${formatDate(s.expiry_date)}</span>
            </td>
            <td>${parseInt(s.straws_available) || 0}</td>
            <td>${parseInt(s.straws_used) || 0}</td>
            <td><span class="badge">${escapeHtml(s.quality_grade || '-')}</span></td>
            <td>
                <button onclick="viewSemenDetails(${s.id})" class="btn btn-secondary text-sm">Ver</button>
            </td>
        </tr>
    `).join('');
}

let currentSemenId = null;

window.openSemenModal = function(id = null) {
    currentSemenId = id;
    const modal = document.getElementById('modal-semen');
    const form = document.getElementById('semen-form');
    const title = document.getElementById('semen-modal-title');
    
    if (!modal || !form) return;
    
    if (id) {
        title.textContent = 'Editar Lote de Sêmen';
        loadSemenData(id);
    } else {
        title.textContent = 'Novo Lote de Sêmen';
        form.reset();
        document.getElementById('semen-id').value = '';
        document.getElementById('semen-production-date').value = new Date().toISOString().split('T')[0];
        document.getElementById('semen-quality').value = 'A';
        document.getElementById('semen-straws-available').value = '0';
        document.getElementById('semen-price').value = '0';
    }
    
    modal.classList.add('active');
};

async function loadSemenData(id) {
    try {
        const response = await fetch(`${API_BASE}?action=semen_list&bull_id=${BULL_ID}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.semen) {
            const semen = result.data.semen.find(s => s.id === id);
            if (semen) {
                document.getElementById('semen-id').value = semen.id;
                document.getElementById('semen-batch').value = semen.batch_number || '';
                document.getElementById('semen-straw-code').value = semen.straw_code || '';
                document.getElementById('semen-production-date').value = semen.production_date || '';
                document.getElementById('semen-collection-date').value = semen.collection_date || '';
                document.getElementById('semen-expiry-date').value = semen.expiry_date || '';
                document.getElementById('semen-straws-available').value = semen.straws_available || 0;
                document.getElementById('semen-price').value = semen.price_per_straw || 0;
                document.getElementById('semen-storage').value = semen.storage_location || '';
                document.getElementById('semen-quality').value = semen.quality_grade || 'A';
                document.getElementById('semen-motility').value = semen.motility || '';
                document.getElementById('semen-volume').value = semen.volume || '';
                document.getElementById('semen-concentration').value = semen.concentration || '';
                document.getElementById('semen-notes').value = semen.notes || '';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar sêmen:', error);
    }
}

async function handleSemenSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('semen-submit-btn');
    const submitText = document.getElementById('semen-submit-text');
    const submitLoading = document.getElementById('semen-submit-loading');
    
    submitBtn.disabled = true;
    submitText.textContent = 'Salvando...';
    submitLoading.classList.remove('hidden');
    
    try {
        const formData = {
            action: 'semen_create',
            bull_id: BULL_ID,
            batch_number: document.getElementById('semen-batch').value,
            straw_code: document.getElementById('semen-straw-code').value,
            production_date: document.getElementById('semen-production-date').value,
            collection_date: document.getElementById('semen-collection-date').value || document.getElementById('semen-production-date').value,
            expiry_date: document.getElementById('semen-expiry-date').value,
            straws_available: parseInt(document.getElementById('semen-straws-available').value) || 0,
            price_per_straw: parseFloat(document.getElementById('semen-price').value) || 0,
            storage_location: document.getElementById('semen-storage').value,
            quality_grade: document.getElementById('semen-quality').value,
            motility: document.getElementById('semen-motility').value || null,
            volume: document.getElementById('semen-volume').value || null,
            concentration: document.getElementById('semen-concentration').value || null,
            notes: document.getElementById('semen-notes').value
        };
        
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.data?.message || 'Sêmen cadastrado com sucesso!');
            closeModal('modal-semen');
            loadSemen();
            loadBullData();
        } else {
            showError(result.error || 'Erro ao salvar sêmen');
        }
    } catch (error) {
        console.error('Erro ao salvar sêmen:', error);
        showError('Erro ao salvar sêmen');
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'Salvar';
        submitLoading.classList.add('hidden');
    }
}

window.viewSemenDetails = function(id) {
    // Pode abrir modal de detalhes ou edição
    openSemenModal(id);
};

// Event listeners configurados na inicialização

// ============================================================
// HISTÓRICO SANITÁRIO
// ============================================================

async function loadHealthRecords() {
    try {
        console.log('Carregando histórico sanitário para touro:', BULL_ID);
        const response = await fetch(`${API_BASE}?action=health_records&bull_id=${BULL_ID}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Resultado histórico sanitário:', result);
        
        if (result.success && result.data) {
            renderHealthTable(result.data.records || []);
        } else {
            console.warn('Resposta sem dados de saúde:', result);
            renderHealthTable([]);
        }
    } catch (error) {
        console.error('Erro ao carregar histórico sanitário:', error);
        renderHealthTable([]);
    }
}

function renderHealthTable(records) {
    const tbody = document.getElementById('health-table-body');
    
    if (!tbody) {
        console.warn('Elemento health-table-body não encontrado');
        return;
    }
    
    if (!records || records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500"></td></tr>';
        return;
    }
    
    console.log('Renderizando', records.length, 'registros sanitários');
    
    tbody.innerHTML = records.map(record => `
        <tr>
            <td>${formatDate(record.record_date)}</td>
            <td><span class="badge">${getRecordTypeLabel(record.record_type || 'outro')}</span></td>
            <td>${escapeHtml(record.record_name || '-')}</td>
            <td>${escapeHtml(record.veterinarian_name || '-')}</td>
            <td>${record.next_due_date ? formatDate(record.next_due_date) : '-'}</td>
            <td>
                <button onclick="viewHealthRecord(${record.id})" class="btn btn-secondary text-sm">Ver</button>
            </td>
        </tr>
    `).join('');
}

let currentHealthId = null;

window.openHealthModal = function(id = null) {
    currentHealthId = id;
    const modal = document.getElementById('modal-health');
    const form = document.getElementById('health-form');
    const title = document.getElementById('health-modal-title');
    
    if (!modal || !form) return;
    
    if (id) {
        title.textContent = 'Editar Registro Sanitário';
        loadHealthData(id);
    } else {
        title.textContent = 'Novo Registro Sanitário';
        form.reset();
        document.getElementById('health-id').value = '';
        document.getElementById('health-date').value = new Date().toISOString().split('T')[0];
    }
    
    modal.classList.add('active');
}

async function loadHealthData(id) {
    try {
        const response = await fetch(`${API_BASE}?action=health_records&bull_id=${BULL_ID}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.records) {
            const record = result.data.records.find(r => r.id === id);
            if (record) {
                document.getElementById('health-id').value = record.id;
                document.getElementById('health-date').value = record.record_date || '';
                document.getElementById('health-type').value = record.record_type || '';
                document.getElementById('health-name').value = record.record_name || '';
                document.getElementById('health-veterinarian').value = record.veterinarian_name || '';
                document.getElementById('health-license').value = record.veterinarian_license || '';
                document.getElementById('health-medication').value = record.medication_name || '';
                document.getElementById('health-dosage').value = record.medication_dosage || '';
                document.getElementById('health-period').value = record.medication_period || '';
                document.getElementById('health-next-date').value = record.next_due_date || '';
                document.getElementById('health-cost').value = record.cost || '';
                document.getElementById('health-results').value = record.results || '';
                document.getElementById('health-notes').value = record.notes || '';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar registro sanitário:', error);
    }
}

async function handleHealthSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('health-submit-btn');
    const submitText = document.getElementById('health-submit-text');
    const submitLoading = document.getElementById('health-submit-loading');
    
    submitBtn.disabled = true;
    submitText.textContent = 'Salvando...';
    submitLoading.classList.remove('hidden');
    
    try {
        const formData = {
            action: 'health_record_create',
            bull_id: BULL_ID,
            record_date: document.getElementById('health-date').value,
            record_type: document.getElementById('health-type').value,
            record_name: document.getElementById('health-name').value,
            veterinarian_name: document.getElementById('health-veterinarian').value,
            veterinarian_license: document.getElementById('health-license').value,
            medication_name: document.getElementById('health-medication').value,
            medication_dosage: document.getElementById('health-dosage').value,
            medication_period: document.getElementById('health-period').value,
            next_due_date: document.getElementById('health-next-date').value,
            cost: document.getElementById('health-cost').value || null,
            results: document.getElementById('health-results').value,
            notes: document.getElementById('health-notes').value
        };
        
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.data?.message || 'Registro sanitário criado com sucesso!');
            closeModal('modal-health');
            loadHealthRecords();
        } else {
            showError(result.error || 'Erro ao salvar registro sanitário');
        }
    } catch (error) {
        console.error('Erro ao salvar registro sanitário:', error);
        showError('Erro ao salvar registro sanitário');
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'Salvar';
        submitLoading.classList.add('hidden');
    }
}

window.viewHealthRecord = function(id) {
    openHealthModal(id);
};

// Event listeners configurados na inicialização

// ============================================================
// HISTÓRICO DE PESO E ESCORE
// ============================================================

async function loadWeightHistory() {
    try {
        console.log('Carregando histórico de peso/escore para touro:', BULL_ID);
        
        // Buscar dados atualizados da API
        const response = await fetch(`${API_BASE}?action=get&id=${BULL_ID}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Resultado histórico peso:', result);
        
        if (result.success && result.data && result.data.body_condition_history) {
            renderWeightHistory(result.data.body_condition_history);
            renderWeightChart(result.data.body_condition_history);
        } else if (bullData && bullData.body_condition_history) {
            renderWeightHistory(bullData.body_condition_history);
            renderWeightChart(bullData.body_condition_history);
        } else {
            console.warn('Sem histórico de peso/escore');
            renderWeightHistory([]);
        }
    } catch (error) {
        console.error('Erro ao carregar histórico de peso:', error);
        renderWeightHistory([]);
    }
}

function renderWeightHistory(history) {
    const tbody = document.getElementById('weight-table-body');
    
    if (!tbody) {
        console.warn('Elemento weight-table-body não encontrado');
        return;
    }
    
    if (!history || history.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-500"></td></tr>';
        return;
    }
    
    console.log('Renderizando histórico de peso/escore:', history.length, 'registros');
    
    tbody.innerHTML = history.map(record => `
        <tr>
            <td>${formatDate(record.record_date)}</td>
            <td>${record.weight || '-'} kg</td>
            <td>${record.body_score || '-'}</td>
            <td>${escapeHtml(record.body_score_notes || '-')}</td>
            <td>
                <button onclick="editWeightRecord(${record.id})" class="btn btn-secondary text-sm">Editar</button>
            </td>
        </tr>
    `).join('');
}

function renderWeightChart(history) {
    if (!history || history.length === 0) {
        console.log('Sem histórico para renderizar gráfico');
        return;
    }
    
    const ctx = document.getElementById('weight-chart');
    if (!ctx) {
        console.warn('Canvas weight-chart não encontrado');
        return;
    }
    
    // Ordenar por data (mais antigo primeiro)
    const sortedHistory = [...history].sort((a, b) => {
        const dateA = new Date(a.record_date);
        const dateB = new Date(b.record_date);
        return dateA - dateB;
    });
    
    const labels = sortedHistory.map(h => formatDate(h.record_date));
    const weights = sortedHistory.map(h => parseFloat(h.weight) || 0);
    const scores = sortedHistory.map(h => parseFloat(h.body_score) || 0);
    
    console.log('Renderizando gráfico de peso/escore:', labels.length, 'pontos');
    
    if (weightChart) {
        weightChart.destroy();
    }
    
    try {
        weightChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Peso (kg)',
                        data: weights,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        yAxisID: 'y',
                        tension: 0.4
                    },
                    {
                        label: 'Escore Corporal',
                        data: scores,
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        yAxisID: 'y1',
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Peso (kg)'
                        },
                        beginAtZero: false
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Escore'
                        },
                        min: 1,
                        max: 5,
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
        console.log('Gráfico de peso/escore renderizado com sucesso');
    } catch (error) {
        console.error('Erro ao renderizar gráfico:', error);
    }
}

let currentWeightId = null;

window.openWeightModal = function(id = null) {
    currentWeightId = id;
    const modal = document.getElementById('modal-weight');
    const form = document.getElementById('weight-form');
    const title = document.getElementById('weight-modal-title');
    
    if (!modal || !form) return;
    
    if (id) {
        title.textContent = 'Editar Registro de Peso/Escore';
        loadWeightData(id);
    } else {
        title.textContent = 'Novo Registro de Peso/Escore';
        form.reset();
        document.getElementById('weight-id').value = '';
        document.getElementById('weight-date').value = new Date().toISOString().split('T')[0];
    }
    
    modal.classList.add('active');
}

async function loadWeightData(id) {
    try {
        const response = await fetch(`${API_BASE}?action=get&id=${BULL_ID}`);
        const result = await response.json();
        
        if (result.success && result.data && result.data.body_condition_history) {
            const record = result.data.body_condition_history.find(r => r.id === id);
            if (record) {
                document.getElementById('weight-id').value = record.id;
                document.getElementById('weight-date').value = record.record_date || '';
                document.getElementById('weight-value').value = record.weight || '';
                document.getElementById('weight-score').value = record.body_score || '';
                document.getElementById('weight-notes').value = record.body_score_notes || '';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar registro de peso:', error);
    }
}

async function handleWeightSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('weight-submit-btn');
    const submitText = document.getElementById('weight-submit-text');
    const submitLoading = document.getElementById('weight-submit-loading');
    
    submitBtn.disabled = true;
    submitText.textContent = 'Salvando...';
    submitLoading.classList.remove('hidden');
    
    try {
        const formData = {
            action: 'body_condition_create',
            bull_id: BULL_ID,
            record_date: document.getElementById('weight-date').value,
            weight: parseFloat(document.getElementById('weight-value').value),
            body_score: parseFloat(document.getElementById('weight-score').value),
            body_score_notes: document.getElementById('weight-notes').value
        };
        
        const response = await fetch(API_BASE, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.data?.message || 'Registro de peso/escore criado com sucesso!');
            closeModal('modal-weight');
            loadWeightHistory();
            loadBullData(); // Atualizar peso atual
        } else {
            showError(result.error || 'Erro ao salvar registro de peso/escore');
        }
    } catch (error) {
        console.error('Erro ao salvar registro de peso:', error);
        showError('Erro ao salvar registro de peso/escore');
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'Salvar';
        submitLoading.classList.add('hidden');
    }
}

window.editWeightRecord = function(id) {
    openWeightModal(id);
};

// Event listeners configurados na inicialização

// ============================================================
// DOCUMENTOS
// ============================================================

async function loadDocuments() {
    try {
        const response = await fetch(`${API_BASE}?action=documents_list&bull_id=${BULL_ID}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            renderDocuments(result.data.documents || []);
        } else if (bullData && bullData.documents) {
            renderDocuments(bullData.documents);
        }
    } catch (error) {
        console.error('Erro ao carregar documentos:', error);
        // Tentar carregar do bullData se houver erro
        if (bullData && bullData.documents) {
            renderDocuments(bullData.documents);
        }
    }
}

function renderDocuments(documents) {
    const container = document.getElementById('documents-list');
    
    if (!documents || documents.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    container.innerHTML = documents.map(doc => `
        <div class="card p-4">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1">
                    <h4 class="font-bold">${escapeHtml(doc.document_name)}</h4>
                    <p class="text-sm text-gray-600">${getDocumentTypeLabel(doc.document_type)}</p>
                </div>
                <button onclick="deleteDocument(${doc.id}, '${escapeHtml(doc.document_name)}')" 
                        class="ml-2 text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
            ${doc.description ? `<p class="text-sm text-gray-600 mb-2">${escapeHtml(doc.description)}</p>` : ''}
            ${doc.issue_date ? `<p class="text-xs text-gray-500 mb-1">Emissão: ${formatDate(doc.issue_date)}</p>` : ''}
            ${doc.expiry_date ? `<p class="text-xs text-gray-500 mb-2">Validade: ${formatDate(doc.expiry_date)}</p>` : ''}
            ${doc.file_size ? `<p class="text-xs text-gray-500 mb-2">Tamanho: ${formatFileSize(doc.file_size)}</p>` : ''}
            <div class="mt-4 flex space-x-2">
                <a href="${doc.file_path}" target="_blank" class="btn btn-primary text-sm flex-1">Visualizar</a>
            </div>
        </div>
    `).join('');
}

window.deleteDocument = async function(id, name) {
    if (!confirm(`Tem certeza que deseja excluir o documento "${name}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(API_BASE, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'document_delete',
                id: id
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Documento removido com sucesso!');
            loadDocuments();
        } else {
            showError(result.error || 'Erro ao remover documento');
        }
    } catch (error) {
        console.error('Erro ao remover documento:', error);
        showError('Erro ao remover documento');
    }
};

function formatFileSize(bytes) {
    if (!bytes) return '-';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

let currentDocumentId = null;

window.openDocumentModal = function(id = null) {
    currentDocumentId = id;
    const modal = document.getElementById('modal-document');
    const form = document.getElementById('document-form');
    const title = document.getElementById('document-modal-title');
    
    if (!modal || !form) return;
    
    if (id) {
        title.textContent = 'Editar Documento';
        // Carregar dados do documento (se necessário)
    } else {
        title.textContent = 'Novo Documento';
        form.reset();
        document.getElementById('document-id').value = '';
    }
    
    modal.classList.add('active');
}

async function handleDocumentSubmit(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('document-submit-btn');
    const submitText = document.getElementById('document-submit-text');
    const submitLoading = document.getElementById('document-submit-loading');
    
    submitBtn.disabled = true;
    submitText.textContent = 'Salvando...';
    submitLoading.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('action', 'document_create');
        formData.append('bull_id', BULL_ID);
        formData.append('document_type', document.getElementById('document-type').value);
        formData.append('document_name', document.getElementById('document-name').value);
        formData.append('issue_date', document.getElementById('document-issue-date').value || '');
        formData.append('expiry_date', document.getElementById('document-expiry-date').value || '');
        formData.append('description', document.getElementById('document-description').value || '');
        
        const fileInput = document.getElementById('document-file');
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        }
        
        const response = await fetch(API_BASE, {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.data?.message || 'Documento salvo com sucesso!');
            closeModal('modal-document');
            loadDocuments();
        } else {
            showError(result.error || 'Erro ao salvar documento');
        }
    } catch (error) {
        console.error('Erro ao salvar documento:', error);
        showError('Erro ao salvar documento');
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'Salvar';
        submitLoading.classList.add('hidden');
    }
}

// Event listeners configurados na inicialização

// ============================================================
// DESCENDENTES
// ============================================================

async function loadOffspring() {
    try {
        console.log('Carregando descendentes para touro:', BULL_ID);
        const response = await fetch(`${API_BASE}?action=offspring&bull_id=${BULL_ID}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Resultado descendentes:', result);
        
        if (result.success && result.data) {
            renderOffspringTable(result.data.offspring || []);
        } else {
            console.warn('Resposta sem dados de descendentes:', result);
            renderOffspringTable([]);
        }
    } catch (error) {
        console.error('Erro ao carregar descendentes:', error);
        renderOffspringTable([]);
    }
}

function renderOffspringTable(offspring) {
    const tbody = document.getElementById('offspring-table-body');
    
    if (!tbody) {
        console.warn('Elemento offspring-table-body não encontrado');
        return;
    }
    
    if (!offspring || offspring.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-500"></td></tr>';
        return;
    }
    
    console.log('Renderizando', offspring.length, 'descendentes');
    
    tbody.innerHTML = offspring.map(o => `
        <tr>
            <td>${escapeHtml(o.animal_number || '-')}</td>
            <td>${escapeHtml(o.animal_name || '-')}</td>
            <td>${escapeHtml(o.breed || '-')}</td>
            <td>${o.birth_date ? formatDate(o.birth_date) : '-'}</td>
            <td>${o.gender === 'macho' ? 'Macho <span class="text-blue-600 font-bold" style="color: #2563eb;">♂</span>' : o.gender === 'femea' ? 'Fêmea <span class="text-pink-600 font-bold" style="color: #db2777;">♀</span>' : '-'}</td>
            <td><span class="badge">${getStatusLabel(o.status || 'ativo')}</span></td>
        </tr>
    `).join('');
}

// ============================================================
// RELATÓRIOS
// ============================================================

async function loadReports() {
    try {
        console.log('Carregando relatórios para touro:', BULL_ID);
        const response = await fetch(`${API_BASE}?action=statistics&bull_id=${BULL_ID}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Resultado relatórios:', result);
        
        if (result.success && result.data) {
            renderReports(result.data);
        } else if (result.success && result) {
            // Se não tem wrapper data, usar resultado direto
            renderReports(result);
        } else {
            console.warn('Sem dados de relatórios:', result);
            renderReports({});
        }
    } catch (error) {
        console.error('Erro ao carregar relatórios:', error);
        renderReports({});
    }
}

function renderReports(data) {
    console.log('Renderizando relatórios com dados:', data);
    
    try {
        // Gráfico de reprodução
        renderReproductionChart(data);
        
        // Estatísticas
        const container = document.getElementById('statistics-summary');
        if (!container) {
            console.warn('Elemento statistics-summary não encontrado');
            return;
        }
        
        const totalServices = data.total_services || 0;
        const overallEfficiency = data.overall_efficiency || 0;
        const totalOffspring = data.total_offspring || 0;
        const semenAvailable = data.semen_straws_available || 0;
        
        container.innerHTML = `
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Total de Serviços</p>
                <p class="text-2xl font-bold">${totalServices}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Taxa de Prenhez</p>
                <p class="text-2xl font-bold">${overallEfficiency.toFixed(1)}%</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Descendentes</p>
                <p class="text-2xl font-bold">${totalOffspring}</p>
            </div>
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">Sêmen Disponível</p>
                <p class="text-2xl font-bold">${semenAvailable}</p>
            </div>
        `;
        
        console.log('Relatórios renderizados com sucesso');
    } catch (error) {
        console.error('Erro ao renderizar relatórios:', error);
        showError('Erro ao exibir relatórios');
    }
}

function renderReproductionChart(data) {
    const ctx = document.getElementById('reproduction-chart');
    if (!ctx) {
        console.warn('Canvas reproduction-chart não encontrado');
        return;
    }
    
    if (reproductionChart) {
        reproductionChart.destroy();
    }
    
    const successful = parseInt(data.total_successful) || 0;
    const failed = parseInt(data.failed_inseminations) || 0;
    const total = parseInt(data.total_services) || 0;
    const pending = Math.max(0, total - successful - failed);
    
    console.log('Dados do gráfico de reprodução:', { successful, failed, pending, total });
    
    try {
        reproductionChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Prenhez', 'Vazia', 'Pendente'],
                datasets: [{
                    data: [successful, failed, pending],
                    backgroundColor: [
                        'rgb(34, 197, 94)',
                        'rgb(239, 68, 68)',
                        'rgb(156, 163, 175)'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        console.log('Gráfico de reprodução renderizado com sucesso');
    } catch (error) {
        console.error('Erro ao renderizar gráfico de reprodução:', error);
    }
    
    // Gráfico de eficiência ao longo do tempo
    renderEfficiencyChart(data);
}

function renderEfficiencyChart(data) {
    const ctx = document.getElementById('efficiency-chart');
    if (!ctx) {
        console.warn('Canvas efficiency-chart não encontrado');
        return;
    }
    
    if (efficiencyChart) {
        efficiencyChart.destroy();
    }
    
    const efficiency = parseFloat(data.overall_efficiency) || 0;
    
    console.log('Renderizando gráfico de eficiência:', efficiency);
    
    try {
        // Gráfico de linha mostrando eficiência (pode ser expandido para mostrar histórico mensal)
        efficiencyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Atual'],
                datasets: [{
                    label: 'Eficiência Reprodutiva (%)',
                    data: [efficiency],
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Eficiência (%)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Eficiência: ${context.parsed.y.toFixed(1)}%`;
                            }
                        }
                    }
                }
            }
        });
        console.log('Gráfico de eficiência renderizado com sucesso');
    } catch (error) {
        console.error('Erro ao renderizar gráfico de eficiência:', error);
    }
}

// ============================================================
// UTILITÁRIOS
// ============================================================

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR');
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

function getSourceLabel(source) {
    const labels = {
        'proprio': 'Próprio',
        'comprado': 'Comprado',
        'arrendado': 'Arrendado',
        'doador_genetico': 'Doador Genético',
        'inseminacao': 'Inseminação'
    };
    return labels[source] || source;
}

function getCoatingTypeLabel(type) {
    const labels = {
        'natural': 'Natural',
        'inseminacao_artificial': 'Inseminação Artificial'
    };
    return labels[type] || type;
}

function getResultLabel(result) {
    const labels = {
        'prenhez': 'Prenhez',
        'vazia': 'Vazia',
        'aborto': 'Aborto',
        'pendente': 'Pendente'
    };
    return labels[result] || result;
}

function getResultClass(result) {
    const classes = {
        'prenhez': 'badge-success',
        'vazia': 'badge-danger',
        'aborto': 'badge-warning',
        'pendente': 'badge-info'
    };
    return classes[result] || 'badge-info';
}

function getRecordTypeLabel(type) {
    const labels = {
        'vacina': 'Vacina',
        'exame_reprodutivo': 'Exame Reprodutivo',
        'exame_laboratorial': 'Exame Laboratorial',
        'tratamento': 'Tratamento',
        'medicamento': 'Medicamento',
        'consulta_veterinaria': 'Consulta Veterinária'
    };
    return labels[type] || type;
}

function getDocumentTypeLabel(type) {
    const labels = {
        'certificado': 'Certificado',
        'laudo': 'Laudo',
        'foto': 'Foto',
        'pedigree': 'Pedigree',
        'teste_genetico': 'Teste Genético',
        'outro': 'Outro'
    };
    return labels[type] || type;
}

function getValidityClass(status) {
    if (status === 'vencido') return 'text-red-600 font-bold';
    if (status === 'proximo_vencimento') return 'text-yellow-600 font-bold';
    return 'text-gray-600';
}

window.closeModal = function(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
    
    // Resetar IDs
    if (modalId === 'modal-coating') currentCoatingId = null;
    if (modalId === 'modal-semen') currentSemenId = null;
    if (modalId === 'modal-health') currentHealthId = null;
    if (modalId === 'modal-weight') currentWeightId = null;
    if (modalId === 'modal-document') currentDocumentId = null;
};

function closeAllModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Função global para voltar
window.goBack = function() {
    window.location.href = 'gerente-completo.php';
};

// Função global para editar touro
window.editBull = function() {
    window.location.href = 'gerente-completo.php';
};

