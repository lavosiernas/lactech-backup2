/**
 * SafeNode - Theme Toggle System
 * Gerencia alternância entre modo claro e escuro
 */

(function() {
    'use strict';
    
    // Verificar preferência salva ou usar padrão (dark)
    const getStoredTheme = () => {
        const stored = localStorage.getItem('safenode-theme');
        if (stored) return stored;
        return 'auto'; // Padrão: seguir dispositivo
    };
    
    // Obter preferência do sistema
    const getSystemTheme = () => {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
            return 'light';
        }
        return 'dark';
    };
    
    // Aplicar tema
    const applyTheme = (theme) => {
        const html = document.documentElement;
        let actualTheme = theme;
        
        // Se for 'auto', usar preferência do sistema
        if (theme === 'auto') {
            actualTheme = getSystemTheme();
        }
        
        if (actualTheme === 'light') {
            html.classList.remove('dark');
        } else {
            html.classList.add('dark');
        }
        
        localStorage.setItem('safenode-theme', theme);
        
        // Disparar evento para atualizar UI
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme, actualTheme } }));
    };
    
    // Listener para mudanças na preferência do sistema (quando tema é 'auto')
    const setupSystemThemeListener = () => {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: light)');
            const handleChange = () => {
                const currentTheme = getStoredTheme();
                if (currentTheme === 'auto') {
                    applyTheme('auto');
                }
            };
            
            // Suporta addEventListener e addListener (para compatibilidade)
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleChange);
            } else {
                mediaQuery.addListener(handleChange);
            }
        }
    };
    
    // Inicializar tema ao carregar
    const initTheme = () => {
        const theme = getStoredTheme();
        applyTheme(theme);
    };
    
    // Toggle tema (cicla: dark -> light -> auto -> dark)
    const toggleTheme = () => {
        const currentTheme = getStoredTheme();
        let newTheme;
        if (currentTheme === 'dark') {
            newTheme = 'light';
        } else if (currentTheme === 'light') {
            newTheme = 'auto';
        } else {
            newTheme = 'dark';
        }
        applyTheme(newTheme);
        return newTheme;
    };
    
    // Obter tema atual (real, não 'auto')
    const getActualTheme = () => {
        const theme = getStoredTheme();
        if (theme === 'auto') {
            return getSystemTheme();
        }
        return theme;
    };
    
    // Expor funções globalmente
    window.SafeNodeTheme = {
        init: initTheme,
        toggle: toggleTheme,
        get: getStoredTheme,
        getActual: getActualTheme,
        set: applyTheme,
        getSystem: getSystemTheme
    };
    
    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initTheme();
            setupSystemThemeListener();
        });
    } else {
        initTheme();
        setupSystemThemeListener();
    }
})();

