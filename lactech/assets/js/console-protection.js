/**
 * Proteção contra cópia de código no console
 * Similar ao sistema do Discord, mas com mensagens personalizadas
 */
(function() {
    'use strict';
    
    // Detectar tentativas de colar código no console
    const originalConsoleLog = console.log;
    const originalConsoleWarn = console.warn;
    const originalConsoleError = console.error;
    const originalConsoleInfo = console.info;
    
    // Mensagens de aviso personalizadas
    const warningMessages = [
        '⚠️ Acesso ao console detectado!',
        '🔒 Este sistema é protegido.',
        '📋 Tentativas de modificar o código são monitoradas.',
        '🚫 Código malicioso será bloqueado automaticamente.',
        '✅ Use apenas funcionalidades autorizadas do sistema.'
    ];
    
    // Função para exibir mensagem de aviso
    function showWarning() {
        const randomMessage = warningMessages[Math.floor(Math.random() * warningMessages.length)];
        console.log('%c' + randomMessage, 'color: #ff0000; font-size: 16px; font-weight: bold; padding: 10px; background: #ffe6e6; border: 2px solid #ff0000; border-radius: 5px;');
        console.log('%cEsta página é protegida. Qualquer tentativa de modificar o código ou executar scripts não autorizados será registrada.', 'color: #666; font-size: 12px;');
    }
    
    // Interceptar console.log
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
            return;
        }
        originalConsoleLog.apply(console, args);
    };
    
    // Interceptar console.warn
    console.warn = function(...args) {
        const message = args.join(' ');
        if (message.includes('Security') || message.includes('Blocked')) {
            showWarning();
        }
        originalConsoleWarn.apply(console, args);
    };
    
    // Interceptar console.error
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('CSP') || message.includes('Content Security Policy')) {
            showWarning();
        }
        originalConsoleError.apply(console, args);
    };
    
    // Detectar tentativas de usar eval
    const originalEval = window.eval;
    window.eval = function(code) {
        showWarning();
        console.error('%cTentativa de usar eval() bloqueada!', 'color: #ff0000; font-weight: bold;');
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
            console.error('%cTentativa de modificar eval() bloqueada!', 'color: #ff0000; font-weight: bold;');
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
    
    // Exibir mensagem inicial quando o console é aberto
    console.log('%c⚡ LacTech - Sistema de Gestão', 'color: #22c55e; font-size: 20px; font-weight: bold;');
    console.log('%cEste console é monitorado por segurança. Use apenas funcionalidades autorizadas.', 'color: #666; font-size: 12px;');
    
})();





