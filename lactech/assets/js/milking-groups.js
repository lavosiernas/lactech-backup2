// Funções para gerenciar Grupos de Ordenha

// Abrir modal de grupos
window.openMilkingGroupsManager = function() {
    const modal = document.getElementById('milkingGroupsModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        loadMilkingGroupsList();
    }
};

// Fechar modal de grupos
window.closeMilkingGroupsModal = function() {
    const modal = document.getElementById('milkingGroupsModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        cancelMilkingGroupForm();
        // Recarregar grupos na página de volume
        if (typeof loadVolumeMilkingGroups === 'function') {
            loadVolumeMilkingGroups();
        }
        if (typeof loadMilkingGroupsPreview === 'function') {
            loadMilkingGroupsPreview();
        }
    }
};

// Carregar lista de grupos
async function loadMilkingGroupsList() {
    const listDiv = document.getElementById('milkingGroupsList');
    if (!listDiv) return;
    
    listDiv.innerHTML = '<p class="text-gray-500 text-center py-8">Carregando grupos...</p>';
    
    try {
        const res = await fetch('./api/volume.php?action=milking_groups_list');
        const result = await res.json();
        
        if (result.success && Array.isArray(result.data)) {
            if (result.data.length === 0) {
                listDiv.innerHTML = '<p class="text-gray-500 text-center py-8">Nenhum grupo criado ainda. Clique em "Novo Grupo" para criar um.</p>';
                return;
            }
            
            listDiv.innerHTML = result.data.map(group => `
                <div class="bg-white border-2 border-gray-200 rounded-lg p-4 flex items-center justify-between hover:border-blue-300 transition-all">
                    <div class="flex-1">
                        <h5 class="font-bold text-gray-800">${group.group_name}</h5>
                        <p class="text-sm text-gray-600">${group.animal_count} vaca${group.animal_count !== 1 ? 's' : ''}</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editMilkingGroup(${group.id})" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-all">
                            Editar
                        </button>
                        <button onclick="deleteMilkingGroup(${group.id}, '${group.group_name}')" class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition-all">
                            Excluir
                        </button>
                    </div>
                </div>
            `).join('');
        } else {
            listDiv.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar grupos</p>';
        }
    } catch (error) {
        console.error('Erro ao carregar grupos:', error);
        listDiv.innerHTML = '<p class="text-red-500 text-center py-8">Erro ao carregar grupos</p>';
    }
}

// Mostrar formulário de criar grupo
window.showCreateMilkingGroupForm = function() {
    const formContainer = document.getElementById('milkingGroupFormContainer');
    const formTitle = document.getElementById('milkingGroupFormTitle');
    const formId = document.getElementById('milkingGroupFormId');
    const formName = document.getElementById('milkingGroupFormName');
    const form = document.getElementById('milkingGroupForm');
    
    if (formContainer && formTitle && formId && formName && form) {
        formTitle.textContent = 'Novo Grupo';
        formId.value = '';
        formName.value = '';
        form.reset();
        formContainer.classList.remove('hidden');
        loadAnimalsForGroupForm([]);
    }
};

// Cancelar formulário
window.cancelMilkingGroupForm = function() {
    const formContainer = document.getElementById('milkingGroupFormContainer');
    if (formContainer) {
        formContainer.classList.add('hidden');
        const form = document.getElementById('milkingGroupForm');
        if (form) form.reset();
    }
};

// Editar grupo
window.editMilkingGroup = async function(groupId) {
    try {
        const today = new Date().toISOString().split('T')[0];
        const res = await fetch(`./api/volume.php?action=milking_group_get&id=${groupId}&date=${today}`);
        const result = await res.json();
        
        if (result.success && result.data) {
            const group = result.data;
            const formContainer = document.getElementById('milkingGroupFormContainer');
            const formTitle = document.getElementById('milkingGroupFormTitle');
            const formId = document.getElementById('milkingGroupFormId');
            const formName = document.getElementById('milkingGroupFormName');
            
            if (formContainer && formTitle && formId && formName) {
                formTitle.textContent = 'Editar Grupo';
                formId.value = group.id;
                formName.value = group.group_name;
                formContainer.classList.remove('hidden');
                // Passar informações sobre turnos já ordenhados
                const animalsWithShifts = group.animals || [];
                loadAnimalsForGroupForm(group.animal_ids || [], animalsWithShifts);
            }
        } else {
            alert('Erro ao carregar grupo: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao carregar grupo:', error);
        alert('Erro ao carregar grupo');
    }
};

// Carregar animais para o formulário
async function loadAnimalsForGroupForm(selectedIds = [], animalsWithShifts = []) {
    const animalsList = document.getElementById('milkingGroupAnimalsList');
    if (!animalsList) return;
    
    animalsList.innerHTML = '<p class="text-gray-500 text-center py-4">Carregando vacas...</p>';
    
    try {
        const res = await fetch('./api/animals.php?action=get_all');
        const result = await res.json();
        
        if (result.success && Array.isArray(result.data)) {
            // Filtrar apenas fêmeas ativas em lactação
            const lactatingFemales = result.data.filter(animal => {
                const isFemale = animal.gender === 'femea';
                const isActive = animal.is_active !== 0 && animal.is_active !== '0';
                const isLactating = !animal.status || 
                                  animal.status.toLowerCase().includes('lactação') || 
                                  animal.status.toLowerCase().includes('lactacao') ||
                                  animal.status.toLowerCase().includes('lactante');
                return isFemale && isActive && isLactating;
            });
            
            if (lactatingFemales.length === 0) {
                animalsList.innerHTML = '<p class="text-gray-500 text-center py-4">Nenhuma vaca encontrada</p>';
                return;
            }
            
            // Criar mapa de turnos já ordenhados
            const shiftsMap = {};
            animalsWithShifts.forEach(animal => {
                if (animal.milked_shifts && Array.isArray(animal.milked_shifts)) {
                    shiftsMap[animal.id] = animal.milked_shifts;
                }
            });
            
            // Função para obter badge de turno
            const getShiftBadge = (shifts) => {
                if (!shifts || shifts.length === 0) return '';
                const shiftNames = {
                    'manha': 'M',
                    'tarde': 'T',
                    'noite': 'N'
                };
                const badges = shifts.map(s => {
                    const color = s === 'manha' ? 'bg-blue-500' : s === 'tarde' ? 'bg-yellow-500' : 'bg-purple-500';
                    return `<span class="inline-block w-5 h-5 ${color} text-white text-xs rounded-full flex items-center justify-center font-bold" title="${shiftNames[s]}">${shiftNames[s]}</span>`;
                });
                return `<span class="flex gap-1 ml-2">${badges.join('')}</span>`;
            };
            
            // Ordenar por número
            lactatingFemales.sort((a, b) => {
                const numA = a.animal_number || '';
                const numB = b.animal_number || '';
                return numA.localeCompare(numB, undefined, { numeric: true, sensitivity: 'base' });
            });
            
            animalsList.innerHTML = lactatingFemales.map(animal => {
                const isChecked = selectedIds.includes(animal.id);
                const milkedShifts = shiftsMap[animal.id] || [];
                const shiftBadges = getShiftBadge(milkedShifts);
                return `
                    <label class="flex items-center p-2 hover:bg-gray-100 rounded cursor-pointer">
                        <input type="checkbox" value="${animal.id}" class="mr-3 w-4 h-4 text-blue-600 rounded" ${isChecked ? 'checked' : ''} onchange="updateSelectedCount()">
                        <span class="flex-1 flex items-center">
                            <span>
                                <span class="font-semibold">${animal.animal_number || 'N/A'}</span>
                                ${animal.name ? ` - ${animal.name}` : ''}
                                <span class="text-sm text-gray-500">(${animal.breed || 'N/A'})</span>
                            </span>
                            ${shiftBadges}
                        </span>
                    </label>
                `;
            }).join('');
            
            updateSelectedCount();
        }
    } catch (error) {
        console.error('Erro ao carregar animais:', error);
        animalsList.innerHTML = '<p class="text-red-500 text-center py-4">Erro ao carregar animais</p>';
    }
}

// Atualizar contador de selecionados
window.updateSelectedCount = function() {
    const checkboxes = document.querySelectorAll('#milkingGroupAnimalsList input[type="checkbox"]');
    const selectedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
    const countElement = document.getElementById('milkingGroupSelectedCount');
    if (countElement) {
        countElement.textContent = `${selectedCount} vaca${selectedCount !== 1 ? 's' : ''} selecionada${selectedCount !== 1 ? 's' : ''}`;
    }
};

// Deletar grupo
window.deleteMilkingGroup = async function(groupId, groupName) {
    if (!confirm(`Tem certeza que deseja excluir o grupo "${groupName}"?`)) {
        return;
    }
    
    try {
        const res = await fetch('./api/volume.php?action=milking_group_delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: groupId })
        });
        
        const result = await res.json();
        
        if (result.success) {
            loadMilkingGroupsList();
            if (typeof loadMilkingGroups === 'function') {
                loadMilkingGroups();
            }
        } else {
            alert('Erro ao excluir grupo: ' + (result.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('Erro ao excluir grupo:', error);
        alert('Erro ao excluir grupo');
    }
};

// Submeter formulário de grupo
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('milkingGroupForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formId = document.getElementById('milkingGroupFormId');
            const formName = document.getElementById('milkingGroupFormName');
            const checkboxes = document.querySelectorAll('#milkingGroupAnimalsList input[type="checkbox"]:checked');
            
            const groupId = formId ? formId.value : '';
            const groupName = formName ? formName.value.trim() : '';
            const animalIds = Array.from(checkboxes).map(cb => parseInt(cb.value));
            
            if (!groupName) {
                alert('Por favor, informe o nome do grupo');
                return;
            }
            
            if (animalIds.length === 0) {
                alert('Por favor, selecione pelo menos uma vaca');
                return;
            }
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn ? submitBtn.textContent : 'Salvar Grupo';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Salvando...';
            }
            
            try {
                const action = groupId ? 'milking_group_update' : 'milking_group_create';
                const res = await fetch(`./api/volume.php?action=${action}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: groupId || undefined,
                        group_name: groupName,
                        animal_ids: animalIds
                    })
                });
                
                const result = await res.json();
                
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
                
                if (result.success) {
                    cancelMilkingGroupForm();
                    loadMilkingGroupsList();
                    if (typeof loadMilkingGroups === 'function') {
                        loadMilkingGroups();
                    }
                } else {
                    alert('Erro ao salvar grupo: ' + (result.error || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao salvar grupo:', error);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
                alert('Erro ao salvar grupo');
            }
        });
    }
});

