// Minimal ScrollReveal
const sr = ScrollReveal({
    origin: 'bottom',
    distance: '30px',
    duration: 600,
    delay: 100,
    reset: false,
    easing: 'ease-out'
});

sr.reveal('.hero-content', { delay: 0 });
sr.reveal('.hero-visual', { delay: 100 });
sr.reveal('.section-title', { delay: 0 });
sr.reveal('.feature-card', { interval: 50 });
sr.reveal('.install-card', { delay: 0 });

// Copy to clipboard
const copyBtn = document.getElementById('copyBtn');
const installCommand = document.getElementById('installCommand');

if (copyBtn && installCommand) {
    copyBtn.addEventListener('click', () => {
        const text = installCommand.innerText;
        navigator.clipboard.writeText(text).then(() => {
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

// Navbar scroll
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// FAQ Accordion
document.querySelectorAll('.faq-question').forEach(question => {
    question.addEventListener('click', () => {
        const faqItem = question.parentElement;
        const isActive = faqItem.classList.contains('active');

        // Close all FAQ items
        document.querySelectorAll('.faq-item').forEach(item => {
            item.classList.remove('active');
        });

        // Toggle current item
        if (!isActive) {
            faqItem.classList.add('active');
        }
    });
});
