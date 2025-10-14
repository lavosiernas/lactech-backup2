// =====================================================
// PERFORMANCE OPTIMIZER - LACTECH SYSTEM
// =====================================================

class PerformanceOptimizer {
    constructor() {
        this.resourceQueue = [];
        this.loadedResources = new Set();
        this.criticalResourcesLoaded = false;
        this.init();
    }

    init() {
        // Preload critical resources
        this.preloadCriticalResources();
        
        // Optimize images
        this.optimizeImages();
        
        // Defer non-critical scripts
        this.deferNonCriticalScripts();
        
        // Setup lazy loading
        this.setupLazyLoading();
        
        // Optimize fonts
        this.optimizeFonts();
        
        console.log('🚀 Performance Optimizer initialized');
    }

    // Preload critical resources
    preloadCriticalResources() {
        const criticalResources = [
            'assets/css/style.css',
            'assets/css/dark-theme-fixes.css'
        ];

        criticalResources.forEach(resource => {
            this.preloadResource(resource, 'style');
        });
    }

    preloadResource(href, as) {
        const link = document.createElement('link');
        link.rel = 'preload';
        link.href = href;
        link.as = as;
        if (as === 'style') {
            link.onload = () => {
                link.rel = 'stylesheet';
            };
        }
        document.head.appendChild(link);
    }

    // Optimize images with lazy loading
    optimizeImages() {
        const images = document.querySelectorAll('img');
        
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

            images.forEach(img => {
                if (img.dataset.src) {
                    img.classList.add('lazy');
                    imageObserver.observe(img);
                }
            });
        }
    }

    // Setup lazy loading for components
    setupLazyLoading() {
        const lazyComponents = document.querySelectorAll('[data-lazy]');
        
        if ('IntersectionObserver' in window) {
            const componentObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadComponent(entry.target);
                        componentObserver.unobserve(entry.target);
                    }
                });
            });

            lazyComponents.forEach(component => {
                componentObserver.observe(component);
            });
        }
    }

    // Load component when needed
    async loadComponent(element) {
        const componentType = element.dataset.lazy;
        
        try {
            switch (componentType) {
                case 'charts':
                    await this.loadCharts();
                    break;
                case 'modals':
                    await this.loadModals();
                    break;
                case 'notifications':
                    await this.loadNotifications();
                    break;
            }
            
            element.classList.add('loaded');
        } catch (error) {
            console.error('Error loading component:', error);
        }
    }

    // Load charts only when needed
    async loadCharts() {
        if (this.loadedResources.has('charts')) return;
        
        // Load Chart.js dynamically
        await this.loadScript('https://cdn.jsdelivr.net/npm/chart.js');
        this.loadedResources.add('charts');
    }

    // Load modal system
    async loadModals() {
        if (this.loadedResources.has('modals')) return;
        
        await this.loadScript('assets/js/modal-system.js');
        this.loadedResources.add('modals');
    }

    // Load notifications
    async loadNotifications() {
        if (this.loadedResources.has('notifications')) return;
        
        await this.loadScript('assets/js/native-notifications.js');
        this.loadedResources.add('notifications');
    }

    // Dynamically load scripts
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // Defer non-critical scripts
    deferNonCriticalScripts() {
        const nonCriticalScripts = [
            'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
            'assets/js/pdf-generator.js',
            'assets/js/offline-sync.js'
        ];

        // Load these after page is fully loaded
        window.addEventListener('load', () => {
            setTimeout(() => {
                nonCriticalScripts.forEach(src => {
                    this.loadScript(src);
                });
            }, 1000);
        });
    }

    // Optimize fonts
    optimizeFonts() {
        // Preload Google Fonts
        const fontLink = document.createElement('link');
        fontLink.rel = 'preload';
        fontLink.href = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap';
        fontLink.as = 'style';
        fontLink.onload = () => {
            fontLink.rel = 'stylesheet';
        };
        document.head.appendChild(fontLink);
    }

    // Critical path optimization
    optimizeCriticalPath() {
        // Hide non-critical content until loaded
        const nonCriticalElements = document.querySelectorAll('[data-non-critical]');
        nonCriticalElements.forEach(el => {
            el.style.visibility = 'hidden';
        });

        // Show content when ready
        window.addEventListener('load', () => {
            setTimeout(() => {
                nonCriticalElements.forEach(el => {
                    el.style.visibility = 'visible';
                });
                document.body.classList.add('loaded');
            }, 100);
        });
    }

    // Database query optimization
    optimizeDatabaseQueries() {
        // Cache frequently accessed data
        const cache = new Map();
        
        return {
            get: async (key) => {
                if (cache.has(key)) {
                    return cache.get(key);
                }
                return null;
            },
            
            set: (key, value, ttl = 300000) => { // 5 minutes default
                cache.set(key, value);
                setTimeout(() => cache.delete(key), ttl);
            }
        };
    }

    // Image compression
    compressImage(file, maxWidth = 800, quality = 0.8) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();
            
            img.onload = () => {
                const ratio = Math.min(maxWidth / img.width, maxWidth / img.height);
                canvas.width = img.width * ratio;
                canvas.height = img.height * ratio;
                
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                canvas.toBlob(resolve, 'image/jpeg', quality);
            };
            
            img.src = URL.createObjectURL(file);
        });
    }

    // Service Worker for caching
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('✅ Service Worker registered:', registration);
            } catch (error) {
                console.log('❌ Service Worker registration failed:', error);
            }
        }
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.performanceOptimizer = new PerformanceOptimizer();
    });
} else {
    window.performanceOptimizer = new PerformanceOptimizer();
}

// Export for global use
window.PerformanceOptimizer = PerformanceOptimizer;
