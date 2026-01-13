/**
 * GitManager - Handles Git operations and UI
 */
class GitManager {
    constructor(ide) {
        this.ide = ide;
        this.currentRepo = null;
        this.stagedFiles = new Set();
        this.modifiedFiles = new Map();
    }

    async init() {
        if (!this.ide.workspace) {
            return;
        }

        console.log('[GitManager] Initializing...');
        await this.checkRepository();
    }

    async checkRepository() {
        if (!this.ide.isElectron || !window.electronAPI.git) {
            return { isRepo: false };
        }

        const result = await window.electronAPI.git.status(this.ide.workspace);
        if (result.success && result.isRepo !== false) {
            this.currentRepo = this.ide.workspace;
            return { isRepo: true };
        }
        return { isRepo: false };
    }

    async initRepository() {
        if (!this.ide.workspace) {
            alert('Please open a folder first');
            return;
        }

        if (!this.ide.isElectron || !window.electronAPI.git) {
            alert('Git operations are only available in desktop mode');
            return;
        }

        const result = await window.electronAPI.git.init(this.ide.workspace);
        if (result.success) {
            this.currentRepo = this.ide.workspace;
            alert('Git repository initialized successfully');
            await this.refreshStatus();
        } else {
            alert('Failed to initialize git repository: ' + result.error);
        }
    }

    async cloneRepository() {
        if (!this.ide.isElectron || !window.electronAPI.git) {
            alert('Git clone is only available in desktop mode');
            return;
        }

        const url = prompt('Enter repository URL:');
        if (!url) return;

        const folderPath = await window.electronAPI.dialog.openDirectory();
        if (!folderPath) return;

        try {
            const result = await window.electronAPI.git.clone(url, folderPath);
            if (result.success) {
                alert('Repository cloned successfully!');
                // Open the cloned folder
                const repoName = url.split('/').pop().replace('.git', '');
                const repoPath = `${folderPath}/${repoName}`;
                await this.ide.openFolderByPath(repoPath);
            } else {
                alert('Failed to clone repository: ' + result.error);
            }
        } catch (error) {
            alert('Error cloning repository: ' + error.message);
        }
    }

    async refreshStatus() {
        if (!this.currentRepo || !this.ide.isElectron || !window.electronAPI.git) {
            return;
        }

        const result = await window.electronAPI.git.status(this.currentRepo);
        if (result.success && result.status) {
            this.parseStatus(result.status);
            this.updateUI();
        }
    }

    parseStatus(statusOutput) {
        this.modifiedFiles.clear();
        this.stagedFiles.clear();

        const lines = statusOutput.split('\n');
        for (const line of lines) {
            if (!line.trim()) continue;

            const status = line.substring(0, 2);
            const filePath = line.substring(3);

            if (status[0] !== ' ' && status[0] !== '?') {
                this.stagedFiles.add(filePath);
            }

            if (status[1] !== ' ') {
                this.modifiedFiles.set(filePath, status);
            }

            if (status === '??') {
                this.modifiedFiles.set(filePath, 'untracked');
            }
        }
    }

    async stageFile(filePath) {
        if (!this.ide.isElectron || !window.electronAPI.git) return;

        const result = await window.electronAPI.git.stage(this.currentRepo, filePath);
        if (result.success) {
            await this.refreshStatus();
        }
    }

    async unstageFile(filePath) {
        if (!this.ide.isElectron || !window.electronAPI.git) return;

        const result = await window.electronAPI.git.unstage(this.currentRepo, filePath);
        if (result.success) {
            await this.refreshStatus();
        }
    }

    async commit() {
        const message = prompt('Commit message:');
        if (!message) return;

        if (!this.ide.isElectron || !window.electronAPI.git) return;

        const result = await window.electronAPI.git.commit(this.currentRepo, message);
        if (result.success) {
            alert('Committed successfully!');
            await this.refreshStatus();
        } else {
            alert('Commit failed: ' + result.error);
        }
    }

    async viewDiff(filePath) {
        if (!this.ide.isElectron || !window.electronAPI.git) return;

        const result = await window.electronAPI.git.diff(this.currentRepo, filePath);
        if (result.success) {
            this.showDiffModal(filePath, result.diff);
        }
    }

    showDiffModal(filePath, diff) {
        // Create a modal to show the diff
        const modal = document.createElement('div');
        modal.className = 'git-diff-modal';
        modal.innerHTML = `
            <div class="modal-overlay" onclick="this.parentElement.remove()"></div>
            <div class="modal-content" style="max-width: 900px; max-height: 80vh;">
                <div class="modal-header">
                    <h3>Changes in ${filePath.split('/').pop()}</h3>
                    <button onclick="this.closest('.git-diff-modal').remove()" class="btn-icon">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <pre style="font-family: 'JetBrains Mono', monospace; font-size: 13px; overflow: auto; max-height: 60vh; background: #000; padding: 1rem; border-radius: 4px; color: #e4e4e7;">${this.formatDiff(diff)}</pre>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Re-initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    formatDiff(diff) {
        if (!diff) return 'No changes';

        return diff.split('\n').map(line => {
            if (line.startsWith('+')) {
                return `<span style="color: #10b981;">${this.escapeHtml(line)}</span>`;
            } else if (line.startsWith('-')) {
                return `<span style="color: #ef4444;">${this.escapeHtml(line)}</span>`;
            } else if (line.startsWith('@@')) {
                return `<span style="color: #3b82f6;">${this.escapeHtml(line)}</span>`;
            }
            return this.escapeHtml(line);
        }).join('\n');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    updateUI() {
        // This will be called by SidebarManager to update the Git view
        const gitView = document.getElementById('sidebarContent');
        if (!gitView || this.ide.sidebarManager.currentView !== 'git') return;

        // Update will be handled by SidebarManager
    }

    getFileStatus(filePath) {
        return this.modifiedFiles.get(filePath) || this.stagedFiles.has(filePath) ? 'staged' : null;
    }
}

window.GitManager = GitManager;
