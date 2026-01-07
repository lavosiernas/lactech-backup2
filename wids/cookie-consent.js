// Cookie Consent Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Check if user already made a choice
    const cookieConsentStatus = localStorage.getItem('cookieConsent');
    
    if (!cookieConsentStatus) {
        // Create cookie consent banner
        const cookieBanner = document.createElement('div');
        cookieBanner.className = 'fixed bottom-5 left-1/2 transform -translate-x-1/2 max-w-4xl w-[95%] bg-white dark:bg-black border border-gray-300 dark:border-zinc-800 text-black dark:text-white py-5 px-6 rounded-lg shadow-xl z-50 opacity-0 translate-y-6';
        cookieBanner.id = 'cookie-banner';
        cookieBanner.innerHTML = `
            <div class="flex items-start md:items-center gap-5 mb-4">
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/>
                        <path d="M8.5 8.5v.01"/>
                        <path d="M16 15.5v.01"/>
                        <path d="M12 12v.01"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold">Sua privacidade importa</h2>
                </div>
            </div>
            
            <div class="mb-5 pl-11 text-sm text-gray-600 dark:text-gray-400">
                <p>A Wide Style utiliza cookies para proporcionar uma experiência personalizada, lembrar suas preferências e melhorar nossos serviços. Todos os dados são tratados com sigilo e de acordo com nossos <a href="termos-legais.html" class="underline hover:text-black dark:hover:text-white transition-colors">termos legais</a>.</p>
            </div>
            
            <div class="pl-11 flex flex-wrap gap-3 md:justify-end">
                <button id="customize-cookies" class="text-sm px-5 py-2.5 rounded-full border border-gray-300 dark:border-zinc-700 hover:border-gray-500 dark:hover:border-zinc-500 transition-colors">
                    Personalizar escolhas
                </button>
                <button id="reject-cookies" class="text-sm px-5 py-2.5 rounded-full border border-gray-300 dark:border-zinc-700 hover:border-gray-500 dark:hover:border-zinc-500 transition-colors">
                    Rejeitar não essenciais
                </button>
                <button id="accept-cookies" class="text-sm bg-black dark:bg-white text-white dark:text-black px-5 py-2.5 rounded-full hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                    Aceitar todos
                </button>
            </div>
        `;
        
        document.body.appendChild(cookieBanner);
        
        // Show banner with animation
        setTimeout(() => {
            cookieBanner.style.opacity = '1';
            cookieBanner.style.transform = 'translateX(-50%) translateY(0)';
        }, 300);
        
        // Function to show notification
        const showNotification = (message, type) => {
            // Check if notification container exists, if not create it
            let notificationContainer = document.getElementById('notification-container');
            if (!notificationContainer) {
                notificationContainer = document.createElement('div');
                notificationContainer.id = 'notification-container';
                notificationContainer.className = 'fixed top-4 right-4 z-50 flex flex-col items-end space-y-2';
                document.body.appendChild(notificationContainer);
            }
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification p-3 rounded-lg shadow-lg flex items-center bg-white dark:bg-zinc-900 text-black dark:text-white transform transition-all duration-300 opacity-0 scale-95 translate-y-2 border border-gray-200 dark:border-zinc-700`;
            
            // Add notification content
            notification.innerHTML = `
                <div class="mr-3 ${type === 'success' ? 'text-green-500' : 'text-blue-500'}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        ${type === 'success' 
                            ? '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>'
                            : '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>'}
                    </svg>
                </div>
                <div class="text-sm font-medium">${message}</div>
            `;
            
            // Add to container
            notificationContainer.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.classList.remove('opacity-0', 'scale-95', 'translate-y-2');
            }, 10);
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        };
        
        // Function to handle cookie consent choice
        const handleConsentChoice = (choice) => {
            localStorage.setItem('cookieConsent', choice);
            cookieBanner.style.opacity = '0';
            cookieBanner.style.transform = 'translateX(-50%) translateY(10px)';
            setTimeout(() => {
                cookieBanner.remove();
            }, 300);
        };
        
        // Add event listeners
        document.getElementById('accept-cookies').addEventListener('click', () => {
            handleConsentChoice('accepted_all');
            showNotification('Preferências de cookies salvas', 'success');
        });
        
        document.getElementById('reject-cookies').addEventListener('click', () => {
            handleConsentChoice('essential_only');
            showNotification('Apenas cookies essenciais serão usados', 'info');
        });
        
        document.getElementById('customize-cookies').addEventListener('click', () => {
            // Redirect to terms page where cookie policies are explained
            window.location.href = 'termos-legais.html';
        });
        
        // Add transition styles
        cookieBanner.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
    }
}); 