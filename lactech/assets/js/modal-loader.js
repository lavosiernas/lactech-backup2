/**
 * Sistema de Carregamento de Modais via Subpáginas
 * Carrega modais de páginas separadas na pasta subs/
 */

(function() {
    'use strict';
    
    const modalContainer = document.createElement('div');
    modalContainer.id = 'modalContainer';
    modalContainer.className = 'fixed inset-0 z-50 hidden';
    document.body.appendChild(modalContainer);
    
    /**
     * Abre um modal carregando uma subpágina
     * @param {string} page - Nome da página (sem .php)
     * @param {object} options - Opções do modal
     */
    function openModal(page, options = {}) {
        const {
            fullscreen = false,
            onClose = null,
            onLoad = null
        } = options;
        
        // Criar overlay
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 bg-black/40 backdrop-blur-sm animate-fadeIn';
        overlay.onclick = () => closeModal();
        
        // Criar iframe
        const iframe = document.createElement('iframe');
        iframe.src = `subs/${page}.php`;
        iframe.className = fullscreen 
            ? 'fixed inset-0 w-full h-full border-0' 
            : 'fixed inset-0 flex items-center justify-center p-4 pointer-events-none';
        iframe.style.pointerEvents = 'auto';
        iframe.style.border = 'none';
        iframe.style.background = fullscreen ? 'white' : 'transparent';
        iframe.style.width = fullscreen ? '100%' : 'auto';
        iframe.style.height = fullscreen ? '100%' : 'auto';
        iframe.style.maxWidth = fullscreen ? '100%' : '90vw';
        iframe.style.maxHeight = fullscreen ? '100%' : '90vh';
        
        // Container do modal
        const modalWrapper = document.createElement('div');
        modalWrapper.className = fullscreen 
            ? 'fixed inset-0 w-full h-full' 
            : 'fixed inset-0 flex items-center justify-center p-4 pointer-events-none';
        modalWrapper.style.pointerEvents = 'auto';
        
        if (!fullscreen) {
            modalWrapper.appendChild(overlay);
        }
        modalWrapper.appendChild(iframe);
        
        // Adicionar ao container
        modalContainer.innerHTML = '';
        modalContainer.appendChild(modalWrapper);
        modalContainer.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Listener para mensagens do iframe
        const messageHandler = (event) => {
            if (event.data.type === 'closeModal') {
                closeModal();
            } else if (event.data.type === 'volumeRegistered' || event.data.type === 'dataSaved') {
                // Recarregar dados se necessário
                if (typeof window.reloadData === 'function') {
                    window.reloadData();
                }
            }
        };
        
        window.addEventListener('message', messageHandler);
        modalWrapper._messageHandler = messageHandler;
        
        // Callback onLoad
        iframe.onload = () => {
            if (onLoad) onLoad(iframe);
        };
        
        // Callback onClose
        modalWrapper._onClose = onClose;
    }
    
    /**
     * Fecha o modal atual
     */
    function closeModal() {
        const modalWrapper = modalContainer.querySelector('div');
        if (modalWrapper && modalWrapper._messageHandler) {
            window.removeEventListener('message', modalWrapper._messageHandler);
        }
        
        if (modalWrapper && modalWrapper._onClose) {
            modalWrapper._onClose();
        }
        
        modalContainer.classList.add('hidden');
        document.body.style.overflow = '';
        
        // Limpar após animação
        setTimeout(() => {
            modalContainer.innerHTML = '';
        }, 300);
    }
    
    // Exportar funções globais
    window.openModal = openModal;
    window.closeModal = closeModal;
    
    // Mapeamento de modais antigos para novas páginas
    const modalMap = {
        'generalVolumeOverlay': 'volume-geral',
        'volumeOverlay': 'volume-vaca',
        'qualityOverlay': 'teste-qualidade',
        'salesOverlay': 'registrar-venda',
        'profileOverlay': 'perfil',
        'addUserModal': 'adicionar-usuario',
        'bulls-modal-fullscreen': 'sistema-touros',
        'moreOptionsModal': 'mais-opcoes',
        'modal-reports': 'relatorios',
        'modal-animals': 'gestao-rebanho',
        'modal-health': 'gestao-sanitaria',
        'modal-reproduction': 'reproducao',
        'modal-analytics': 'dashboard-analitico',
        'modal-actions': 'central-acoes',
        'modal-groups': 'grupos-lotes',
        'modal-support': 'suporte',
        'modal-feeding': 'alimentacao'
    };
    
    // Interceptar chamadas antigas de modais
    document.addEventListener('DOMContentLoaded', () => {
        // Substituir funções antigas
        if (typeof window.openGeneralVolumeOverlay === 'undefined') {
            window.openGeneralVolumeOverlay = () => openModal('volume-geral');
            window.closeGeneralVolumeOverlay = closeModal;
        }
        
        if (typeof window.openVolumeOverlay === 'undefined') {
            window.openVolumeOverlay = () => openModal('volume-vaca');
            window.closeVolumeOverlay = closeModal;
        }
        
        if (typeof window.openQualityOverlay === 'undefined') {
            window.openQualityOverlay = () => openModal('teste-qualidade');
            window.closeQualityOverlay = closeModal;
        }
        
        if (typeof window.openSalesOverlay === 'undefined') {
            window.openSalesOverlay = () => openModal('registrar-venda');
            window.closeSalesOverlay = closeModal;
        }
        
        if (typeof window.openProfileOverlay === 'undefined') {
            window.openProfileOverlay = () => openModal('perfil', { fullscreen: true });
            window.closeProfileOverlay = closeModal;
        }
        
        if (typeof window.openAddUserModal === 'undefined') {
            window.openAddUserModal = () => openModal('adicionar-usuario');
            window.closeAddUserModal = closeModal;
        }
        
        if (typeof window.openBullsModal === 'undefined') {
            window.openBullsModal = () => openModal('sistema-touros', { fullscreen: true });
            window.closeBullsModal = closeModal;
        }
        
        if (typeof window.openMoreOptionsModal === 'undefined') {
            // Mais Opções agora é uma página completa, não modal
            window.openMoreOptionsModal = () => {
                window.location.href = 'mais-opcoes.php';
            };
        }
        
        // Funções para submodais do Mais Opções
        if (typeof window.openSubModal === 'undefined') {
            window.openSubModal = (modalName) => {
                const pageMap = {
                    'reports': 'relatorios',
                    'animals': 'gestao-rebanho',
                    'health': 'gestao-sanitaria',
                    'reproduction': 'reproducao',
                    'analytics': 'dashboard-analitico',
                    'actions': 'central-acoes',
                    'groups': 'grupos-lotes',
                    'support': 'suporte',
                    'feeding': 'alimentacao'
                };
                const page = pageMap[modalName] || modalName;
                openModal(page, { fullscreen: true });
            };
        }
        
        if (typeof window.closeSubModal === 'undefined') {
            window.closeSubModal = closeModal;
        }
    });
    
    // Listener para mensagens de subpáginas
    window.addEventListener('message', (event) => {
        if (event.data.type === 'openModal' || event.data.type === 'openSubPage') {
            openModal(event.data.page, { fullscreen: event.data.fullscreen || false });
        }
    });
    
})();

