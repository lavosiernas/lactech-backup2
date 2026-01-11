/**
 * SidebarManager - Manages sidebar views (Explorer, Search, Git, Extensions)
 */

export class SidebarManager {
    constructor(ide) {
        this.ide = ide;
        this.currentView = 'explorer';
        this.workspacePath = null;
        this.setupEventListeners();
        this.showExplorer();
    }

    setupEventListeners() {
        const tabs = document.querySelectorAll('.sidebar-tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const view = tab.dataset.view;
                this.switchView(view);
            });
        });
    }

    switchView(view) {
        // Update active tab
        const tabs = document.querySelectorAll('.sidebar-tab');
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.view === view);
        });

        this.currentView = view;

        // Show appropriate content
        switch (view) {
            case 'explorer':
                this.showExplorer();
                break;
            case 'search':
                this.showSearch();
                break;
            case 'git':
                this.showGit();
                break;
            case 'extensions':
                this.showExtensions();
                break;
        }
    }

    showExplorer() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        if (this.workspacePath) {
            content.innerHTML = `
        <div class="sidebar-section">
          <div class="section-header">
            <span>EXPLORER</span>
            <div class="section-actions">
              <button class="btn-icon-xs" id="btnNewFile" title="New File">
                <i data-lucide="file-plus" class="w-3 h-3"></i>
              </button>
              <button class="btn-icon-xs" id="btnNewFolder" title="New Folder">
                <i data-lucide="folder-plus" class="w-3 h-3"></i>
              </button>
              <button class="btn-icon-xs" id="btnRefreshExplorer" title="Refresh">
                <i data-lucide="refresh-cw" class="w-3 h-3"></i>
              </button>
            </div>
          </div>
          <div class="file-tree" id="fileTree">
            <div class="loading">Loading...</div>
          </div>
        </div>
      `;

            this.loadFileTree();
        } else {
            content.innerHTML = `
        <div class="sidebar-section">
          <div class="section-header">
            <span>EXPLORER</span>
          </div>
          <div class="empty-state">
            <p>No folder opened</p>
            <button id="btnOpenFolderSidebar" class="btn-primary-sm">
              <i data-lucide="folder-open" class="w-3 h-3"></i>
              Open Folder
            </button>
          </div>
        </div>
      `;

            const btnOpenFolder = document.getElementById('btnOpenFolderSidebar');
            if (btnOpenFolder) {
                btnOpenFolder.addEventListener('click', () => this.ide.openFolder());
            }
        }

        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    async loadWorkspace(path) {
        this.workspacePath = path;
        this.showExplorer();
    }

    async loadFileTree() {
        const fileTree = document.getElementById('fileTree');
        if (!fileTree || !this.workspacePath) return;

        try {
            const items = await this.ide.fileSystem.readDirectory(this.workspacePath);
            fileTree.innerHTML = this.renderFileTree(items, this.workspacePath);

            // Setup click handlers
            this.setupFileTreeHandlers();

            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Error loading file tree:', error);
            fileTree.innerHTML = `<div class="error">Error loading files</div>`;
        }
    }

    renderFileTree(items, basePath) {
        const sorted = items.sort((a, b) => {
            if (a.isDirectory && !b.isDirectory) return -1;
            if (!a.isDirectory && b.isDirectory) return 1;
            return a.name.localeCompare(b.name);
        });

        return sorted.map(item => {
            const icon = item.isDirectory ? 'folder' : this.getFileIcon(item.name);
            const className = item.isDirectory ? 'tree-folder' : 'tree-file';

            return `
        <div class="tree-item ${className}" data-path="${item.path}">
          <i data-lucide="${icon}" class="w-4 h-4"></i>
          <span>${item.name}</span>
        </div>
      `;
        }).join('');
    }

    setupFileTreeHandlers() {
        const items = document.querySelectorAll('.tree-item');
        items.forEach(item => {
            item.addEventListener('click', async () => {
                const path = item.dataset.path;
                const isFolder = item.classList.contains('tree-folder');

                if (isFolder) {
                    // TODO: Expand/collapse folder
                    console.log('Folder clicked:', path);
                } else {
                    // Open file
                    await this.ide.openFileByPath(path);
                }
            });
        });
    }

    async refreshExplorer() {
        if (this.currentView === 'explorer') {
            await this.loadFileTree();
        }
    }

    showSearch() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        content.innerHTML = `
      <div class="sidebar-section">
        <div class="section-header">
          <span>SEARCH</span>
        </div>
        <div class="search-container">
          <input type="text" class="search-input" placeholder="Search in files..." />
          <div class="search-results">
            <div class="empty-state">
              <p>No results</p>
            </div>
          </div>
        </div>
      </div>
    `;
    }

    showGit() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        content.innerHTML = `
      <div class="sidebar-section">
        <div class="section-header">
          <span>SOURCE CONTROL</span>
        </div>
        <div class="empty-state">
          <p>Git integration coming soon</p>
        </div>
      </div>
    `;
    }

    showExtensions() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;

        content.innerHTML = `
      <div class="sidebar-section">
        <div class="section-header">
          <span>EXTENSIONS</span>
        </div>
        <div class="empty-state">
          <p>Extension marketplace coming soon</p>
        </div>
      </div>
    `;
    }

    getFileIcon(fileName) {
        const ext = fileName.split('.').pop()?.toLowerCase();

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
