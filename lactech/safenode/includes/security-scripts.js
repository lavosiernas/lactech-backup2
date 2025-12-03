/**
 * SafeNode - Scripts de Segurança
 * Previne download e inspeção de código-fonte
 */

(function() {
    'use strict';
    
    // Prevenir Ctrl+S (Salvar página)
    document.addEventListener('keydown', function(e) {
        // Ctrl+S ou Cmd+S
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Ctrl+Shift+I (DevTools)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'I') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Ctrl+Shift+J (Console)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'J') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Ctrl+U (Ver código-fonte)
        if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // F12 (DevTools)
        if (e.key === 'F12') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        
        // Ctrl+Shift+C (Inspetor)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    }, true);
    
    // Prevenir clique direito (opcional - pode ser irritante para usuários)
    // Descomente se quiser bloquear completamente
    /*
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    */
    
    // Prevenir seleção de texto (opcional - pode ser irritante)
    // Descomente se quiser bloquear completamente
    /*
    document.addEventListener('selectstart', function(e) {
        e.preventDefault();
        return false;
    });
    */
    
    // Prevenir arrastar imagens
    document.addEventListener('dragstart', function(e) {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
            return false;
        }
    });
    
    // Desabilitar DevTools quando aberto (detecção básica)
    let devtools = {open: false, orientation: null};
    const threshold = 160;
    
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                // Opcional: redirecionar ou mostrar aviso
                // window.location.href = 'about:blank';
            }
        } else {
            devtools.open = false;
        }
    }, 500);
    
    // Prevenir cópia via atalhos
    document.addEventListener('keydown', function(e) {
        // Ctrl+C, Ctrl+A, Ctrl+X (pode ser muito restritivo)
        // Descomente se quiser bloquear completamente
        /*
        if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'a' || e.key === 'x')) {
            e.preventDefault();
            return false;
        }
        */
    }, true);
    
    // Console warning
    console.log('%c⚠️ Acesso Restrito', 'color: red; font-size: 20px; font-weight: bold;');
    console.log('%cEsta área é restrita. Não execute código aqui.', 'color: red; font-size: 14px;');
    
})();

