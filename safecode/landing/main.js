// Initialize ScrollReveal
const sr = ScrollReveal({
    origin: 'bottom',
    distance: '60px',
    duration: 1000,
    delay: 200,
    reset: false
});

// Animations
sr.reveal('.hero-content', { delay: 100 });
sr.reveal('.hero-visual', { delay: 300, origin: 'right' });
sr.reveal('.section-title');
sr.reveal('.feature-card', { interval: 100 });
sr.reveal('.install-card', { delay: 200 });

// Copy to Clipboard
const copyBtn = document.getElementById('copyBtn');
const installCommand = document.getElementById('installCommand');

if (copyBtn && installCommand) {
    copyBtn.addEventListener('click', () => {
        const text = installCommand.innerText;
        navigator.clipboard.writeText(text).then(() => {
            // Visual feedback
            const icon = copyBtn.querySelector('i');
            const originalData = icon.getAttribute('data-lucide');

            icon.setAttribute('data-lucide', 'check');
            lucide.createIcons();
            copyBtn.style.color = '#10b981';

            setTimeout(() => {
                icon.setAttribute('data-lucide', originalData);
                lucide.createIcons();
                copyBtn.style.color = '';
            }, 2000);
        });
    });
}

// Navbar scroll effect
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.style.background = 'rgba(5, 5, 5, 0.8)';
        navbar.style.padding = '0.5rem 0';
    } else {
        navbar.style.background = 'transparent';
        navbar.style.padding = '1.5rem 0';
    }
});
