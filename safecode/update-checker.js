/**
 * SafeCode IDE - Update Checker
 * Checks for new versions on GitHub and notifies the user.
 */

class UpdateChecker {
    constructor(ide) {
        this.ide = ide;
        this.currentVersion = '1.0.0';
        // Your public version info URL (mocked for now)
        this.versionInfoUrl = 'https://raw.githubusercontent.com/user/safecode-ide/main/version.json';
    }

    async checkForUpdates() {
        console.log('🔍 Checking for updates...');

        try {
            // Simulated check (since real URL might not exist yet)
            // In production, you'd fetch(this.versionInfoUrl)
            const response = await this.simulateFetch();

            if (this.isNewer(response.version, this.currentVersion)) {
                this.showUpdateNotification(response.version);
            } else {
                console.log('✅ SafeCode IDE is up to date.');
            }
        } catch (error) {
            console.warn('⚠️ Could not check for updates:', error);
        }
    }

    isNewer(latest, current) {
        const l = latest.split('.').map(Number);
        const c = current.split('.').map(Number);
        for (let i = 0; i < 3; i++) {
            if (l[i] > c[i]) return true;
            if (l[i] < c[i]) return false;
        }
        return false;
    }

    showUpdateNotification(newVersion) {
        // Create a monochrome update notification in the status bar or as a button
        const header = document.querySelector('.header-actions');
        if (!header) return;

        const updateBtn = document.createElement('button');
        updateBtn.className = 'btn-update-available';
        updateBtn.innerHTML = `<i data-lucide="arrow-up-circle" style="width: 14px; height: 14px;"></i> Update Available: v${newVersion}`;
        updateBtn.onclick = () => {
            // Open Landing Page
            if (typeof require !== 'undefined') {
                require('electron').shell.openExternal('https://safecode-ide.com/download');
            } else {
                window.open('https://safecode-ide.com/download', '_blank');
            }
        };

        header.prepend(updateBtn);
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    async simulateFetch() {
        return new Promise((resolve) => {
            setTimeout(() => {
                // Change to '1.0.1' to test the notification
                resolve({ version: '1.0.0' });
            }, 2000);
        });
    }
}
