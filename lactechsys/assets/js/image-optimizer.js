// =====================================================
// IMAGE OPTIMIZER - LACTECH SYSTEM
// =====================================================

class ImageOptimizer {
    constructor() {
        this.lazyImages = [];
        this.observer = null;
        this.init();
    }

    init() {
        this.setupLazyLoading();
        this.optimizeExistingImages();
        console.log('🖼️ Image Optimizer initialized');
    }

    // Setup lazy loading for images
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            this.observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadImage(entry.target);
                        this.observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.1
            });

            // Find all images that need lazy loading
            const images = document.querySelectorAll('img[data-src]');
            images.forEach(img => {
                this.observer.observe(img);
            });
        }
    }

    // Load image when it comes into view
    loadImage(img) {
        const src = img.dataset.src;
        if (!src) return;

        // Create new image to preload
        const imageLoader = new Image();
        
        imageLoader.onload = () => {
            img.src = src;
            img.classList.remove('lazy');
            img.classList.add('loaded');
        };

        imageLoader.onerror = () => {
            img.classList.add('error');
            console.warn('Failed to load image:', src);
        };

        imageLoader.src = src;
    }

    // Optimize existing images
    optimizeExistingImages() {
        const images = document.querySelectorAll('img:not([data-src])');
        
        images.forEach(img => {
            // Add loading="lazy" if supported
            if ('loading' in HTMLImageElement.prototype) {
                img.loading = 'lazy';
            }
            
            // Optimize image attributes
            this.optimizeImageAttributes(img);
        });
    }

    // Optimize image attributes
    optimizeImageAttributes(img) {
        // Ensure proper sizing
        if (!img.width && !img.height) {
            img.style.maxWidth = '100%';
            img.style.height = 'auto';
        }

        // Add proper alt text if missing
        if (!img.alt) {
            img.alt = 'LacTech System Image';
        }

        // Add decoding attribute for better performance
        img.decoding = 'async';
    }

    // Compress image before upload
    async compressImage(file, options = {}) {
        const {
            maxWidth = 1200,
            maxHeight = 1200,
            quality = 0.8,
            format = 'image/jpeg'
        } = options;

        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = () => {
                // Calculate new dimensions
                let { width, height } = img;
                
                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width *= ratio;
                    height *= ratio;
                }

                // Set canvas dimensions
                canvas.width = width;
                canvas.height = height;

                // Draw and compress
                ctx.drawImage(img, 0, 0, width, height);
                
                canvas.toBlob(resolve, format, quality);
            };

            img.src = URL.createObjectURL(file);
        });
    }

    // Generate responsive image sources
    generateResponsiveSources(src, sizes = [320, 640, 1024, 1200]) {
        return sizes.map(size => {
            // This would integrate with your image processing service
            return `${src}?w=${size}&q=80&f=webp`;
        });
    }

    // Preload critical images
    preloadCriticalImages(imageUrls) {
        imageUrls.forEach(url => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = url;
            document.head.appendChild(link);
        });
    }

    // Convert to WebP format if supported
    supportsWebP() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }

    // Get optimized image URL
    getOptimizedImageUrl(originalUrl, options = {}) {
        const {
            width,
            height,
            quality = 80,
            format = this.supportsWebP() ? 'webp' : 'jpeg'
        } = options;

        let optimizedUrl = originalUrl;

        // Add query parameters for optimization
        const params = new URLSearchParams();
        if (width) params.append('w', width);
        if (height) params.append('h', height);
        params.append('q', quality);
        params.append('f', format);

        if (params.toString()) {
            optimizedUrl += (originalUrl.includes('?') ? '&' : '?') + params.toString();
        }

        return optimizedUrl;
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.imageOptimizer = new ImageOptimizer();
    });
} else {
    window.imageOptimizer = new ImageOptimizer();
}

// Export for global use
window.ImageOptimizer = ImageOptimizer;
