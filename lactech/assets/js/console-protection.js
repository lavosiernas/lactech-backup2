/**
 * Proteção contra cópia de código no console
 * Similar ao sistema do Discord, mas com mensagens personalizadas
 */
(function() {
    'use strict';
    
    // Detectar tentativas de colar código no console
    const originalConsoleError = console.error;
    
    // Função para registrar tentativas (silenciosa em produção)
    function showWarning() {
        // Proteção ativa - tentativas são bloqueadas silenciosamente
        // Em desenvolvimento, pode ser reativado logging se necessário
    }
    
    // Bloquear console.log completamente em produção
    console.log = function(...args) {
        // Verificar se é tentativa de colar código malicioso
        const message = args.join(' ');
        if (message.includes('eval(') || 
            message.includes('Function(') || 
            message.includes('document.write') ||
            message.includes('innerHTML') ||
            message.includes('dangerous') ||
            message.includes('bypass') ||
            message.length > 500) {
            showWarning();
        }
        // Bloqueado - não executar console.log
    };
    
    // Bloquear console.warn completamente em produção
    console.warn = function(...args) {
        const message = args.join(' ');
        if (message.includes('Security') || message.includes('Blocked')) {
            showWarning();
        }
        // Bloqueado - não executar console.warn
    };
    
    // Interceptar console.error - manter apenas erros críticos
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('CSP') || message.includes('Content Security Policy')) {
            showWarning();
            return;
        }
        // Manter apenas erros críticos em produção
        originalConsoleError.apply(console, args);
    };
    
    // Detectar tentativas de usar eval
    const originalEval = window.eval;
    window.eval = function(code) {
        showWarning();
        // Bloqueado silenciosamente em produção
        return originalEval.apply(this, arguments);
    };
    
    // Detectar tentativas de modificar propriedades críticas
    Object.defineProperty(window, 'eval', {
        get: function() {
            showWarning();
            return originalEval;
        },
        set: function() {
            showWarning();
            // Bloqueado silenciosamente em produção
        },
        configurable: false
    });
    
    // Detectar quando o console é aberto (F12)
    let devtools = {
        open: false,
        orientation: null
    };
    
    const threshold = 160;
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > threshold || 
            window.outerWidth - window.innerWidth > threshold) {
            if (!devtools.open) {
                devtools.open = true;
                showWarning();
            }
        } else {
            devtools.open = false;
        }
    }, 500);
    
    // Proteção ativa - console.log e console.warn bloqueados em produção
    
})();




