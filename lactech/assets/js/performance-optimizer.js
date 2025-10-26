/**
 * Performance Optimizer - Lactech
 * Otimizações de performance para o sistema
 */

class PerformanceOptimizer {
    constructor() {
        this.observers = new Map();
        this.debounceTimers = new Map();
        this.throttleTimers = new Map();
        this.init();
    }

    init() {
        console.log('🚀 Performance Optimizer inicializado');
        this.setupLazyLoading();
        this.setupImageOptimization();
        this.setupScrollOptimization();
        this.setupResizeOptimization();
        this.setupMemoryManagement();
    }

    /**
     * Lazy Loading para imagens e elementos
     */
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            observer.unobserve(img);
                        }
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    /**
     * Otimização de imagens
     */
    setupImageOptimization() {
        // Comprimir imagens automaticamente
        document.querySelectorAll('img').forEach(img => {
            if (!img.hasAttribute('data-optimized')) {
                img.setAttribute('data-optimized', 'true');
                img.style.imageRendering = 'auto';
                img.style.objectFit = 'cover';
            }
        });
    }

    /**
     * Otimização de scroll
     */
    setupScrollOptimization() {
        let scrollTimeout;
        let isScrolling = false;

        const handleScroll = () => {
            if (!isScrolling) {
                isScrolling = true;
                requestAnimationFrame(() => {
                    // Otimizações durante o scroll
                    document.body.classList.add('scrolling');
                    isScrolling = false;
                });
            }
        };

        window.addEventListener('scroll', handleScroll, { passive: true });

        // Limpar classe após parar de rolar
        window.addEventListener('scroll', () => {
            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                document.body.classList.remove('scrolling');
            }, 150);
        }, { passive: true });
    }

    /**
     * Otimização de resize
     */
    setupResizeOptimization() {
        let resizeTimeout;
        
        const handleResize = () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                // Otimizações durante o resize
                this.optimizeLayout();
            }, 250);
        };

        window.addEventListener('resize', handleResize, { passive: true });
    }

    /**
     * Gerenciamento de memória
     */
    setupMemoryManagement() {
        // Limpar observers não utilizados
        setInterval(() => {
            this.cleanupObservers();
        }, 30000); // A cada 30 segundos

        // Limpar timers não utilizados
        setInterval(() => {
            this.cleanupTimers();
        }, 60000); // A cada 1 minuto
    }

    /**
     * Debounce para funções
     */
    debounce(func, delay, key = 'default') {
        return (...args) => {
            if (this.debounceTimers.has(key)) {
                clearTimeout(this.debounceTimers.get(key));
            }
            
            const timer = setTimeout(() => {
                func.apply(this, args);
                this.debounceTimers.delete(key);
            }, delay);
            
            this.debounceTimers.set(key, timer);
        };
    }

    /**
     * Throttle para funções
     */
    throttle(func, delay, key = 'default') {
        return (...args) => {
            if (this.throttleTimers.has(key)) {
                return;
            }
            
            func.apply(this, args);
            
            const timer = setTimeout(() => {
                this.throttleTimers.delete(key);
            }, delay);
            
            this.throttleTimers.set(key, timer);
        };
    }

    /**
     * Otimizar layout
     */
    optimizeLayout() {
        // Recalcular posições de elementos fixos
        const fixedElements = document.querySelectorAll('.fixed, .sticky');
        fixedElements.forEach(element => {
            element.style.transform = 'translateZ(0)';
        });

        // Otimizar tabelas grandes
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (table.rows.length > 100) {
                table.style.contain = 'layout';
            }
        });
    }

    /**
     * Limpar observers não utilizados
     */
    cleanupObservers() {
        this.observers.forEach((observer, key) => {
            if (observer.disconnected) {
                this.observers.delete(key);
            }
        });
    }

    /**
     * Limpar timers não utilizados
     */
    cleanupTimers() {
        // Limpar timers expirados
        this.debounceTimers.forEach((timer, key) => {
            if (Date.now() - timer.timestamp > 300000) { // 5 minutos
                clearTimeout(timer);
                this.debounceTimers.delete(key);
            }
        });

        this.throttleTimers.forEach((timer, key) => {
            if (Date.now() - timer.timestamp > 300000) { // 5 minutos
                clearTimeout(timer);
                this.throttleTimers.delete(key);
            }
        });
    }

    /**
     * Otimizar performance de animações
     */
    optimizeAnimations() {
        // Usar transform em vez de position para animações
        const animatedElements = document.querySelectorAll('.animate, .transition');
        animatedElements.forEach(element => {
            element.style.willChange = 'transform, opacity';
        });
    }

    /**
     * Preload de recursos críticos
     */
    preloadCriticalResources() {
        const criticalResources = [
            'assets/css/style.css',
            'assets/css/tailwind.css',
            'assets/js/gerente.js'
        ];

        criticalResources.forEach(resource => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = resource;
            link.as = resource.endsWith('.css') ? 'style' : 'script';
            document.head.appendChild(link);
        });
    }

    /**
     * Otimizar carregamento de dados
     */
    optimizeDataLoading() {
        // Implementar cache inteligente
        if ('caches' in window) {
            caches.open('lactech-data').then(cache => {
                console.log('📦 Cache de dados inicializado');
            });
        }
    }

    /**
     * Métricas de performance
     */
    getPerformanceMetrics() {
        const metrics = {
            loadTime: performance.timing.loadEventEnd - performance.timing.navigationStart,
            domContentLoaded: performance.timing.domContentLoadedEventEnd - performance.timing.navigationStart,
            firstPaint: performance.getEntriesByType('paint')[0]?.startTime || 0,
            memoryUsage: performance.memory ? {
                used: performance.memory.usedJSHeapSize,
                total: performance.memory.totalJSHeapSize,
                limit: performance.memory.jsHeapSizeLimit
            } : null
        };

        return metrics;
    }

    /**
     * Relatório de performance
     */
    generatePerformanceReport() {
        const metrics = this.getPerformanceMetrics();
        
        console.group('📊 Relatório de Performance');
        console.log('⏱️ Tempo de carregamento:', metrics.loadTime + 'ms');
        console.log('📄 DOM Content Loaded:', metrics.domContentLoaded + 'ms');
        console.log('🎨 First Paint:', metrics.firstPaint + 'ms');
        
        if (metrics.memoryUsage) {
            console.log('💾 Uso de memória:', {
                usado: Math.round(metrics.memoryUsage.used / 1024 / 1024) + 'MB',
                total: Math.round(metrics.memoryUsage.total / 1024 / 1024) + 'MB',
                limite: Math.round(metrics.memoryUsage.limit / 1024 / 1024) + 'MB'
            });
        }
        
        console.groupEnd();
        
        return metrics;
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.performanceOptimizer = new PerformanceOptimizer();
    
    // Gerar relatório de performance após 3 segundos
    setTimeout(() => {
        window.performanceOptimizer.generatePerformanceReport();
    }, 3000);
});

// Exportar para uso global
window.PerformanceOptimizer = PerformanceOptimizer;

