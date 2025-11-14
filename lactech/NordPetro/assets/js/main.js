(function () {
    const counters = document.querySelectorAll('[data-count]');
    const animateCounters = () => {
        counters.forEach((el) => {
            const target = Number(el.dataset.count);
            const suffix = el.dataset.suffix ?? '';
            let current = 0;
            const duration = 1200;
            const start = performance.now();

            const tick = (now) => {
                const progress = Math.min((now - start) / duration, 1);
                current = Math.floor(progress * target);
                el.textContent = current + suffix;
                if (progress < 1) requestAnimationFrame(tick);
                else el.textContent = target + suffix;
            };

            requestAnimationFrame(tick);
        });
    };

    const revealItems = document.querySelectorAll('[data-reveal]');
    const handleScroll = () => {
        const trigger = window.innerHeight * 0.85;
        revealItems.forEach((el) => {
            if (el.classList.contains('revealed')) {
                return;
            }
            const top = el.getBoundingClientRect().top;
            if (top < trigger) {
                el.classList.add('revealed');
            }
        });
    };

    window.addEventListener('load', () => {
        animateCounters();
        handleScroll();
    });

    window.addEventListener('scroll', handleScroll, { passive: true });
})();

