/**
 * SafeCode IDE - Enhanced Version
 * With Monaco Editor, Live Preview, and Full Functionality
 */

// Global IDE instance
let ide = null;

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIDE);
} else {
    initIDE();
}

async function initIDE() {
    console.log('🚀 SafeCode IDE Enhanced initializing...');

    // Initialize Monaco Editor
    await initMonaco();

    // Create IDE instance
    ide = new SafeCodeIDE();
    window.ide = ide;

    console.log('✅ SafeCode IDE Enhanced ready!');
}

// Initialize Monaco Editor
async function initMonaco() {
    return new Promise((resolve) => {
        if (typeof require === 'undefined') {
            // Monaco not available, will use fallback
            console.warn('Monaco Editor not available, using fallback');
            resolve();
            return;
        }

        require.config({
            paths: {
                'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs'
            }
        });

        require(['vs/editor/editor.main'], function () {
            console.log('✅ Monaco Editor loaded');
            resolve();
        });
    });
}

// ============================================================================
// Main IDE Class
// ============================================================================
class SafeCodeIDE {
    constructor() {
        this.isElectron = typeof window !== 'undefined' && window.electronAPI;
        this.settingsManager = new SettingsManager(this);

        // Dynamic FS Selection
        if (this.isElectron) {
            this.fileSystem = new FileSystemManager();
        } else {
            console.log('🌐 Loading SafeCode Web Edition Engine...');
            this.fileSystem = new WebFileSystemManager();
        }
        this.editorManager = new MonacoEditorManager(this);
        this.sidebarManager = new EnhancedSidebarManager(this);
        this.tabManager = new EnhancedTabManager(this);
        this.terminalManager = new TerminalManager(this);
        this.previewManager = new LivePreviewManager(this);
        this.liveServer = new LiveServer(this);
        this.commandPalette = new CommandPalette(this);
        this.settingsView = new SettingsView(this);
        this.updateChecker = new UpdateChecker(this);
        this.extensionManager = new ExtensionManager(this);

        this.currentFile = null;
        this.workspace = null;

        // DOM References
        this.bottomPanel = document.getElementById('bottomPanel');
        this.editorWelcome = document.getElementById('editorWelcome');

        this.init();
    }

    async init() {
        this.setupEventListeners();
        this.setupMenuListeners();
        this.setupKeyboardShortcuts();
        this.initializeLucideIcons();

        // Initialize Extension System
        await this.extensionManager.init();

        // Apply settings
        this.settingsManager.applySettings();

        // Setup panel handlers
        this.setupPanelHandlers();

        // Load recent projects
        this.loadRecentProjects();

        // Check for updates
        this.updateChecker.checkForUpdates();

        // Auto-hide sidebar if empty
        if (!this.workspace) {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.add('hidden');
        }
    }

    setupEventListeners() {
        // Welcome screen
        document.getElementById('btnOpenFile')?.addEventListener('click', () => this.openFile());
        document.getElementById('btnOpenFolder')?.addEventListener('click', () => this.openFolder());
        document.getElementById('btnNewFile')?.addEventListener('click', () => this.newFile());

        // Header
        document.getElementById('btnGoLive')?.addEventListener('click', () => this.toggleLiveServer());
        document.getElementById('btnSettings')?.addEventListener('click', () => this.showSettings());
        document.getElementById('btnCommandPalette')?.addEventListener('click', () => this.commandPalette.toggle());

        // Preview
        document.getElementById('btnRefreshPreview')?.addEventListener('click', () => this.previewManager.refresh());
        document.getElementById('btnClosePreview')?.addEventListener('click', () => this.previewManager.close());
        document.getElementById('btnOpenExternal')?.addEventListener('click', () => this.previewManager.openExternal());
        document.getElementById('btnCopyUrl')?.addEventListener('click', () => this.previewManager.copyUrl());

        // Device buttons
        document.querySelectorAll('.device-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const device = e.currentTarget.dataset.device;
                this.previewManager.setDevice(device);
            });
        });

        // Panel
        document.getElementById('btnClosePanel')?.addEventListener('click', () => this.toggleBottomPanel());
        document.getElementById('btnNewTerminal')?.addEventListener('click', () => this.terminalManager.createTerminal());
        document.getElementById('btnSplitTerminal')?.addEventListener('click', () => this.terminalManager.splitTerminal());

        // Tabs
        document.getElementById('btnCloseAll')?.addEventListener('click', () => this.tabManager.closeAll());

        // File system events
        this.fileSystem.on('fileChanged', (path) => this.editorManager.reloadFile(path));
        this.fileSystem.on('fileAdded', () => this.sidebarManager.refreshExplorer());
        this.fileSystem.on('fileDeleted', () => this.sidebarManager.refreshExplorer());

        // Custom HTML Menus
        this.setupHTMLMenuListeners();
    }

    setupHTMLMenuListeners() {
        // File
        document.getElementById('menuItemNewFile')?.addEventListener('click', () => this.newFile());
        document.getElementById('menuItemOpenFile')?.addEventListener('click', () => this.openFile());
        document.getElementById('menuItemOpenFolder')?.addEventListener('click', () => this.openFolder());
        document.getElementById('menuItemSave')?.addEventListener('click', () => this.saveCurrentFile());
        document.getElementById('menuItemSaveAs')?.addEventListener('click', () => this.saveCurrentFileAs());

        // View
        document.getElementById('menuItemToggleSidebar')?.addEventListener('click', () => this.toggleSidebar());
        document.getElementById('menuItemToggleTerminal')?.addEventListener('click', () => this.toggleBottomPanel());
        document.getElementById('menuItemTogglePreview')?.addEventListener('click', () => this.previewManager.toggle());

        // Terminal
        document.getElementById('menuItemNewTerminal')?.addEventListener('click', () => {
            if (this.bottomPanel?.style.display === 'none') this.toggleBottomPanel();
            this.terminalManager.createTerminal();
        });
        document.getElementById('menuItemClearTerminal')?.addEventListener('click', () => this.terminalManager.clearActiveTerminal());

        // Extensions
        document.getElementById('menuItemManageExtensions')?.addEventListener('click', () => this.sidebarManager.switchView('extensions'));

        // Help
        document.getElementById('menuItemWelcome')?.addEventListener('click', () => {
            const welcome = document.getElementById('editorWelcome');
            if (welcome) welcome.style.display = 'flex';
        });
        document.getElementById('menuItemAbout')?.addEventListener('click', () => alert('SafeCode IDE v1.0.0\nA professional monochrome development environment.'));

        // Edit
        document.getElementById('menuItemUndo')?.addEventListener('click', () => this.editorManager.getActiveEditor()?.trigger('keyboard', 'undo', null));
        document.getElementById('menuItemRedo')?.addEventListener('click', () => this.editorManager.getActiveEditor()?.trigger('keyboard', 'redo', null));
        document.getElementById('menuItemCut')?.addEventListener('click', () => document.execCommand('cut'));
        document.getElementById('menuItemCopy')?.addEventListener('click', () => document.execCommand('copy'));
        document.getElementById('menuItemPaste')?.addEventListener('click', () => document.execCommand('paste'));
    }

    setupMenuListeners() {
        if (!this.isElectron) return;

        const api = window.electronAPI.menu;
        api.onNewFile(() => this.newFile());
        api.onOpenFile((filePath) => this.openFileByPath(filePath));
        api.onOpenFolder((folderPath) => this.openFolderByPath(folderPath));
        api.onSave(() => this.saveCurrentFile());
        api.onSaveAs(() => this.saveCurrentFileAs());
        api.onToggleSidebar(() => this.toggleSidebar());
        api.onToggleTerminal(() => this.toggleBottomPanel());
        api.onTogglePreview(() => this.previewManager.toggle());
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            const ctrl = e.ctrlKey || e.metaKey;
            const shift = e.shiftKey;

            if (ctrl && !shift && e.key === 'n') {
                e.preventDefault();
                this.newFile();
            }
            if (ctrl && !shift && e.key === 'o') {
                e.preventDefault();
                this.openFile();
            }
            if (ctrl && shift && e.key.toLowerCase() === 'o') {
                e.preventDefault();
                this.openFolder();
            }
            if (ctrl && !shift && e.key === 's') {
                e.preventDefault();
                this.saveCurrentFile();
            }
            if (ctrl && shift && e.key.toLowerCase() === 's') {
                e.preventDefault();
                this.saveCurrentFileAs();
            }
            if (ctrl && shift && e.key.toLowerCase() === 'p') {
                e.preventDefault();
                this.commandPalette.toggle();
            }
            if (ctrl && !shift && e.key === 'b') {
                e.preventDefault();
                this.toggleSidebar();
            }
            if (ctrl && e.key === '`') {
                e.preventDefault();
                this.toggleBottomPanel();
            }
            if (ctrl && !shift && e.key === 'w') {
                e.preventDefault();
                this.tabManager.closeActiveTab();
            }
            if (ctrl && shift && e.key.toLowerCase() === 'v') {
                e.preventDefault();
                this.previewManager.toggle();
            }
            if (e.key === 'Escape') {
                this.commandPalette.close();
            }
        });
    }

    initializeLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    async newFile() {
        const fileName = `Untitled-${Date.now()}.txt`;
        await this.editorManager.createEditor(fileName, '');
        this.tabManager.addTab(fileName);
        this.hideWelcomeScreen();
    }

    async openFile() {
        if (!this.isElectron) {
            alert('File opening is only available in desktop mode');
            return;
        }

        const filePath = await this.fileSystem.showOpenDialog();
        if (filePath) {
            await this.openFileByPath(filePath);
        }
    }

    async openFileByPath(filePath) {
        try {
            const content = await this.fileSystem.readFile(filePath);
            await this.editorManager.createEditor(filePath, content);
            this.tabManager.addTab(filePath);
            this.currentFile = filePath;
            this.hideWelcomeScreen();
            this.addToRecentProjects(filePath);

            // Emit event to extensions
            this.extensionManager.emitEvent('file-opened', filePath);
        } catch (error) {
            console.error('Error opening file:', error);
            alert('Error opening file: ' + error.message);
        }
    }

    async openFolder() {
        if (!this.isElectron) {
            alert('Folder opening is only available in desktop mode');
            return;
        }

        const folderPath = await this.fileSystem.showOpenFolderDialog();
        if (folderPath) {
            await this.openFolderByPath(folderPath);
        }
    }

    async openFolderByPath(folderPath) {
        try {
            await this.fileSystem.openWorkspace(folderPath);
            this.workspace = folderPath;

            // Auto-show sidebar
            const sidebar = document.getElementById('sidebar');
            if (sidebar) sidebar.classList.remove('hidden');

            this.sidebarManager.loadWorkspace(folderPath);
            this.hideWelcomeScreen();
            this.addToRecentProjects(folderPath, true);
        } catch (error) {
            console.error('Error opening folder:', error);
            alert('Error opening folder: ' + error.message);
        }
    }

    async saveCurrentFile() {
        if (!this.currentFile || this.currentFile.startsWith('Untitled-')) {
            return this.saveCurrentFileAs();
        }

        try {
            const content = this.editorManager.getCurrentContent();
            await this.fileSystem.writeFile(this.currentFile, content);
            this.tabManager.markSaved(this.currentFile);

            // Emit event to extensions
            this.extensionManager.emitEvent('file-saved', this.currentFile);

            // Auto-refresh preview if live server is running
            if (this.liveServer.isRunning) {
                this.previewManager.refresh();
            }
        } catch (error) {
            console.error('Error saving file:', error);
            alert('Error saving file: ' + error.message);
        }
    }

    async saveCurrentFileAs() {
        if (!this.isElectron) {
            alert('Save As is only available in desktop mode');
            return;
        }

        const filePath = await this.fileSystem.showSaveDialog();
        if (filePath) {
            try {
                const content = this.editorManager.getCurrentContent();
                await this.fileSystem.writeFile(filePath, content);

                const oldFile = this.currentFile;
                this.currentFile = filePath;
                this.tabManager.renameTab(oldFile, filePath);
            } catch (error) {
                console.error('Error saving file:', error);
                alert('Error saving file: ' + error.message);
            }
        }
    }

    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar?.classList.toggle('hidden');
    }

    toggleBottomPanel() {
        const panel = document.getElementById('bottomPanel');
        if (panel) {
            if (panel.style.display === 'none' || !panel.style.display) {
                panel.style.display = 'flex';
                if (this.terminalManager.terminals.size === 0) {
                    this.terminalManager.createTerminal();
                }
            } else {
                panel.style.display = 'none';
            }
        }
    }

    toggleLiveServer() {
        if (this.liveServer.isRunning) {
            this.liveServer.stop();
        } else {
            this.liveServer.start();
        }
    }

    hideWelcomeScreen() {
        const welcome = document.getElementById('editorWelcome');
        if (welcome) welcome.style.display = 'none';
    }

    switchPanel(panelId) {
        const panel = document.getElementById('bottomPanel');
        if (panel) {
            panel.style.display = 'flex';

            // Update tabs
            document.querySelectorAll('.panel-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.panel === panelId);
            });

            // Update views
            document.querySelectorAll('.panel-view').forEach(view => {
                view.classList.toggle('active', view.id === `panel-${panelId}`);
            });

            if (panelId === 'terminal' && this.terminalManager) {
                // Resize if possible
                if (typeof this.terminalManager.resizeTerminals === 'function') {
                    this.terminalManager.resizeTerminals();
                }
            }
        }
    }

    setupPanelHandlers() {
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.addEventListener('click', () => this.switchPanel(tab.dataset.panel));
        });

        const btnClosePanel = document.getElementById('btnClosePanel');
        if (btnClosePanel) {
            btnClosePanel.onclick = () => this.toggleBottomPanel();
        }
    }

    showSettings() {
        this.settingsView?.show();
    }

    loadRecentProjects() {
        const recent = localStorage.getItem('safecode_recent_projects');
        if (recent) {
            try {
                const projects = JSON.parse(recent);
                this.displayRecentProjects(projects);
            } catch (e) {
                console.error('Error loading recent projects:', e);
            }
        }
    }

    addToRecentProjects(path, isFolder = false) {
        let recent = [];
        try {
            recent = JSON.parse(localStorage.getItem('safecode_recent_projects') || '[]');
        } catch (e) { }

        // Remove if already exists
        recent = recent.filter(p => p.path !== path);

        // Add to beginning
        recent.unshift({
            path,
            isFolder,
            timestamp: Date.now()
        });

        // Keep only last 10
        recent = recent.slice(0, 10);

        localStorage.setItem('safecode_recent_projects', JSON.stringify(recent));
        this.displayRecentProjects(recent);
    }

    displayRecentProjects(projects) {
        const container = document.querySelector('.recent-list');
        if (!container || projects.length === 0) return;

        container.innerHTML = projects.map(p => {
            const name = p.path.split(/[/\\]/).pop();
            const icon = p.isFolder ? 'folder' : 'file';
            return `
                <div class="recent-item" data-path="${p.path}" data-is-folder="${p.isFolder}">
                    <i data-lucide="${icon}" class="w-4 h-4"></i>
                    <div>
                        <div style="font-weight: 500; color: var(--text-primary);">${name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-tertiary);">${p.path}</div>
                    </div>
                </div>
            `;
        }).join('');

        // Add click handlers
        container.querySelectorAll('.recent-item').forEach(item => {
            item.addEventListener('click', () => {
                const path = item.dataset.path;
                const isFolder = item.dataset.isFolder === 'true';
                if (isFolder) {
                    this.openFolderByPath(path);
                } else {
                    this.openFileByPath(path);
                }
            });
        });

        this.initializeLucideIcons();
    }
}

// Continue in next file due to length...
