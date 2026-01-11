/**
 * TabManager - Manages editor tabs
 */

export class TabManager {
    constructor(ide) {
        this.ide = ide;
        this.tabs = new Map(); // filePath -> tab element
        this.activeTab = null;
        this.setupEventListeners();
    }

    setupEventListeners() {
        const tabsContainer = document.getElementById('tabsContainer');
        if (tabsContainer) {
            tabsContainer.addEventListener('click', (e) => {
                const tab = e.target.closest('.editor-tab');
                if (tab) {
                    const filePath = tab.dataset.file;

                    // Check if close button was clicked
                    if (e.target.closest('.tab-close')) {
                        this.closeTab(filePath);
                    } else {
                        this.activateTab(filePath);
                    }
                }
            });
        }
    }

    addTab(filePath, content) {
        // Check if tab already exists
        if (this.tabs.has(filePath)) {
            this.activateTab(filePath);
            return;
        }

        const tabsContainer = document.getElementById('tabsContainer');
        if (!tabsContainer) return;

        // Create tab element
        const tab = document.createElement('div');
        tab.className = 'editor-tab active';
        tab.dataset.file = filePath;

        const fileName = this.getFileName(filePath);
        const icon = this.getFileIcon(filePath);

        tab.innerHTML = `
      <i data-lucide="${icon}" class="w-3 h-3"></i>
      <span class="tab-label">${fileName}</span>
      <button class="tab-close">
        <i data-lucide="x" class="w-3 h-3"></i>
      </button>
    `;

        // Deactivate all other tabs
        this.deactivateAllTabs();

        // Add to container
        tabsContainer.appendChild(tab);
        this.tabs.set(filePath, tab);
        this.activeTab = filePath;

        // Initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    activateTab(filePath) {
        this.deactivateAllTabs();

        const tab = this.tabs.get(filePath);
        if (tab) {
            tab.classList.add('active');
            this.activeTab = filePath;
            this.ide.editorManager.showEditor(filePath);
            this.ide.currentFile = filePath;
        }
    }

    deactivateAllTabs() {
        this.tabs.forEach(tab => {
            tab.classList.remove('active');
        });
    }

    closeTab(filePath) {
        const tab = this.tabs.get(filePath);
        if (!tab) return;

        // Check if file is dirty
        if (tab.classList.contains('dirty')) {
            const confirm = window.confirm(`Save changes to ${this.getFileName(filePath)}?`);
            if (confirm) {
                this.ide.saveCurrentFile();
            }
        }

        // Remove tab
        tab.remove();
        this.tabs.delete(filePath);

        // Close editor
        this.ide.editorManager.closeEditor(filePath);

        // Activate another tab if available
        if (this.tabs.size > 0) {
            const firstTab = Array.from(this.tabs.keys())[0];
            this.activateTab(firstTab);
        } else {
            this.activeTab = null;
            this.ide.currentFile = null;
        }
    }

    closeAll() {
        const filePaths = Array.from(this.tabs.keys());
        filePaths.forEach(filePath => this.closeTab(filePath));
    }

    closeActiveTab() {
        if (this.activeTab) {
            this.closeTab(this.activeTab);
        }
    }

    markDirty(filePath) {
        const tab = this.tabs.get(filePath);
        if (tab && !tab.classList.contains('dirty')) {
            tab.classList.add('dirty');
            const label = tab.querySelector('.tab-label');
            if (label && !label.textContent.startsWith('● ')) {
                label.textContent = '● ' + label.textContent;
            }
        }
    }

    markSaved(filePath) {
        const tab = this.tabs.get(filePath);
        if (tab) {
            tab.classList.remove('dirty');
            const label = tab.querySelector('.tab-label');
            if (label && label.textContent.startsWith('● ')) {
                label.textContent = label.textContent.substring(2);
            }
        }
    }

    renameTab(oldPath, newPath) {
        const tab = this.tabs.get(oldPath);
        if (tab) {
            tab.dataset.file = newPath;
            const label = tab.querySelector('.tab-label');
            if (label) {
                const fileName = this.getFileName(newPath);
                label.textContent = label.textContent.startsWith('● ') ? '● ' + fileName : fileName;
            }

            this.tabs.delete(oldPath);
            this.tabs.set(newPath, tab);

            if (this.activeTab === oldPath) {
                this.activeTab = newPath;
            }
        }
    }

    getActiveTab() {
        return this.activeTab;
    }

    getFileName(filePath) {
        const parts = filePath.split(/[/\\]/);
        return parts[parts.length - 1];
    }

    getFileIcon(filePath) {
        const ext = filePath.split('.').pop()?.toLowerCase();

        const iconMap = {
            'js': 'file-code',
            'jsx': 'file-code',
            'ts': 'file-code',
            'tsx': 'file-code',
            'html': 'file-code',
            'css': 'file-code',
            'json': 'braces',
            'md': 'file-text',
            'py': 'file-code',
            'php': 'file-code',
            'txt': 'file-text'
        };

        return iconMap[ext] || 'file';
    }
}
