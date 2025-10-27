// OTIMIZADOR DE PERFORMANCE - FASE 2
console.log('⚡ Carregando otimizador de performance...');

class PerformanceOptimizer {
    constructor() {
        this.cache = new Map();
        this.debounceTimers = new Map();
        this.observers = new Map();
        this.init();
    }
    
    init() {
        this.setupLazyLoading();
        this.setupImageOptimization();
        this.setupDataCaching();
        this.setupDebouncing();
        this.setupMemoryManagement();
        console.log('⚡ Otimizador de performance inicializado!');
    }
    
    // 1. LAZY LOADING
    setupLazyLoading() {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                }
            });
        }, { rootMargin: '50px' });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
    
    // 2. OTIMIZAÇÃO DE IMAGENS
    setupImageOptimization() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            // Adicionar loading lazy nativo
            if (!img.loading) {
                img.loading = 'lazy';
            }
            
            // Otimizar imagens grandes
            if (img.naturalWidth > 800) {
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
            }
        });
    }
    
    // 3. CACHE DE DADOS
    setupDataCaching() {
        // Interceptar fetch para cache
        const originalFetch = window.fetch;
        window.fetch = async (url, options = {}) => {
            const cacheKey = `${url}_${JSON.stringify(options)}`;
            
            // Verificar cache
            if (this.cache.has(cacheKey)) {
                const cached = this.cache.get(cacheKey);
                if (Date.now() - cached.timestamp < 300000) { // 5 minutos
                    console.log('📦 Cache hit:', url);
                    return Promise.resolve(new Response(JSON.stringify(cached.data)));
                }
            }
            
            // Fazer requisição
            const response = await originalFetch(url, options);
            const data = await response.json();
            
            // Armazenar no cache
            this.cache.set(cacheKey, {
                data: data,
                timestamp: Date.now()
            });
            
            return response;
        };
    }
    
    // 4. DEBOUNCING
    setupDebouncing() {
        // Debounce para pesquisas
        this.debounce = (func, wait) => {
            return (...args) => {
                const key = func.name || 'anonymous';
                clearTimeout(this.debounceTimers.get(key));
                this.debounceTimers.set(key, setTimeout(() => func.apply(this, args), wait));
            };
        };
        
        // Aplicar debounce em inputs de pesquisa
        document.querySelectorAll('input[type="search"], input[placeholder*="pesquisar"], input[placeholder*="buscar"]').forEach(input => {
            const debouncedSearch = this.debounce((e) => {
                // Implementar lógica de pesquisa
                console.log('🔍 Pesquisando:', e.target.value);
            }, 300);
            
            input.addEventListener('input', debouncedSearch);
        });
    }
    
    // 5. GERENCIAMENTO DE MEMÓRIA
    setupMemoryManagement() {
        // Limpar cache periodicamente
        setInterval(() => {
            const now = Date.now();
            for (const [key, value] of this.cache.entries()) {
                if (now - value.timestamp > 600000) { // 10 minutos
                    this.cache.delete(key);
                }
            }
        }, 300000); // 5 minutos
        
        // Limpar observers quando não precisar mais
        window.addEventListener('beforeunload', () => {
            this.observers.forEach(observer => observer.disconnect());
            this.cache.clear();
        });
    }
    
    // 6. OTIMIZAÇÃO DE DOM
    optimizeDOM() {
        // Remover elementos não utilizados
        const unusedElements = document.querySelectorAll('.unused, .deprecated, [style*="display: none"]');
        unusedElements.forEach(el => {
            if (!el.hasAttribute('data-keep')) {
                el.remove();
            }
        });
        
        // Otimizar seletores
        const heavySelectors = document.querySelectorAll('[class*="hover:"], [class*="focus:"]');
        heavySelectors.forEach(el => {
            el.style.willChange = 'transform';
        });
    }
    
    // 7. COMPRESSÃO DE DADOS
    compressData(data) {
        // Simular compressão (em produção, usar biblioteca real)
        return JSON.stringify(data);
    }
    
    // 8. MÉTRICAS DE PERFORMANCE
    getPerformanceMetrics() {
        const metrics = {
            cacheSize: this.cache.size,
            memoryUsage: performance.memory ? performance.memory.usedJSHeapSize : 'N/A',
            loadTime: performance.timing ? performance.timing.loadEventEnd - performance.timing.navigationStart : 'N/A',
            domNodes: document.querySelectorAll('*').length
        };
        
        console.table(metrics);
        return metrics;
    }
}

// Inicializar otimizador
const performanceOptimizer = new PerformanceOptimizer();

// Otimizações específicas para gráficos
function optimizeCharts() {
    const chartContainers = document.querySelectorAll('.chart-container');
    chartContainers.forEach(container => {
        // Adicionar resize observer
        const resizeObserver = new ResizeObserver(entries => {
            entries.forEach(entry => {
                // Redimensionar gráfico se necessário
                const chart = entry.target.querySelector('canvas');
                if (chart && chart.chart) {
                    chart.chart.resize();
                }
            });
        });
        
        resizeObserver.observe(container);
        performanceOptimizer.observers.set(container, resizeObserver);
    });
}

// Otimizações para modais
function optimizeModals() {
    const modals = document.querySelectorAll('[id*="Modal"]');
    modals.forEach(modal => {
        // Lazy load conteúdo do modal
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    // Carregar conteúdo quando modal for visível
                    const lazyContent = entry.target.querySelector('.lazy-content');
                    if (lazyContent && lazyContent.dataset.src) {
                        fetch(lazyContent.dataset.src)
                            .then(response => response.text())
                            .then(html => {
                                lazyContent.innerHTML = html;
                                lazyContent.classList.remove('lazy-content');
                            });
                    }
                    observer.unobserve(entry.target);
                }
            });
        });
        
        observer.observe(modal);
        performanceOptimizer.observers.set(modal, observer);
    });
}

// Inicializar otimizações específicas
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        optimizeCharts();
        optimizeModals();
        performanceOptimizer.optimizeDOM();
    }, 1000);
});

// Expor para debug
window.performanceOptimizer = performanceOptimizer;

console.log('⚡ Otimizador de performance carregado!');