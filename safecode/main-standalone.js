/**
 * SafeCode IDE - Main Entry Point (Standalone Version)
 * This version loads all dependencies from CDN
 */

// Wait for DOM to be ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIDE);
} else {
    initIDE();
}

async function initIDE() {
    console.log('🚀 SafeCode IDE initializing...');

    // Load dependencies from CDN
    await loadDependencies();

    // Initialize IDE
    const ide = new SafeCodeIDE();
    window.ide = ide;

    console.log('✅ SafeCode IDE ready!');
}

async function loadDependencies() {
    // Load CodeMirror from CDN
    const cmScript = document.createElement('script');
    cmScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.js';
    cmScript.type = 'module';
    document.head.appendChild(cmScript);

    // Load xterm.js from CDN
    const xtermScript = document.createElement('script');
    xtermScript.src = 'https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.js';
    document.head.appendChild(xtermScript);

    const xtermFitScript = document.createElement('script');
    xtermFitScript.src = 'https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.js';
    document.head.appendChild(xtermFitScript);

    const xtermWebLinksScript = document.createElement('script');
    xtermWebLinksScript.src = 'https://cdn.jsdelivr.net/npm/xterm-addon-web-links@0.9.0/lib/xterm-addon-web-links.js';
    document.head.appendChild(xtermWebLinksScript);

    // Wait for scripts to load
    await new Promise(resolve => setTimeout(resolve, 1000));
}

class SafeCodeIDE {
    constructor() {
        this.fileSystem = new FileSystemManager();
        this.editorManager = new EditorManager(this);
        this.sidebarManager = new SidebarManager(this);
        this.tabManager = new TabManager(this);
        this.terminalManager = new TerminalManager(this);
        this.currentFile = null;
        this.isElectron = typeof window !== 'undefined' && window.electronAPI;

        this.setupEventListeners();
        this.setupMenuListeners();
        this.setupKeyboardShortcuts();

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    setupEventListeners() {
        // Welcome screen buttons
        const btnOpenFile = document.getElementById('btnOpenFile');
        const btnOpenFolder = document.getElementById('btnOpenFolder');

        if (btnOpenFile) {
            btnOpenFile.addEventListener('click', () => this.openFile());
        }

        if (btnOpenFolder) {
            btnOpenFolder.addEventListener('click', () => this.openFolder());
        }

        // Header buttons
        const btnSettings = document.getElementById('btnSettings');
        const btnCommandPalette = document.getElementById('btnCommandPalette');

        if (btnSettings) {
            btnSettings.addEventListener('click', () => this.showSettings());
        }

        if (btnCommandPalette) {
            btnCommandPalette.addEventListener('click', () => this.toggleCommandPalette());
        }

        // Preview buttons
        const btnClosePreview = document.getElementById('btnClosePreview');
        const btnRefreshPreview = document.getElementById('btnRefreshPreview');

        if (btnClosePreview) {
            btnClosePreview.addEventListener('click', () => this.togglePreview());
        }

        if (btnRefreshPreview) {
            btnRefreshPreview.addEventListener('click', () => this.refreshPreview());
        }

        // Panel buttons
        const btnClosePanel = document.getElementById('btnClosePanel');
        const btnNewTerminal = document.getElementById('btnNewTerminal');
        const btnSplitTerminal = document.getElementById('btnSplitTerminal');

        if (btnClosePanel) {
            btnClosePanel.addEventListener('click', () => this.toggleBottomPanel());
        }

        if (btnNewTerminal) {
            btnNewTerminal.addEventListener('click', () => this.terminalManager.createTerminal());
        }

        if (btnSplitTerminal) {
            btnSplitTerminal.addEventListener('click', () => this.terminalManager.splitTerminal());
        }

        // Tab actions
        const btnCloseAll = document.getElementById('btnCloseAll');
        if (btnCloseAll) {
            btnCloseAll.addEventListener('click', () => this.tabManager.closeAll());
        }

        // File system events
        this.fileSystem.on('fileChanged', (path) => {
            console.log('File changed:', path);
            this.editorManager.reloadFile(path);
        });

        this.fileSystem.on('fileAdded', (path) => {
            console.log('File added:', path);
            this.sidebarManager.refreshExplorer();
        });

        this.fileSystem.on('fileDeleted', (path) => {
            console.log('File deleted:', path);
            this.sidebarManager.refreshExplorer();
        });
    }

    setupMenuListeners() {
        if (!this.isElectron) {
            console.log('Not running in Electron, menu listeners disabled');
            return;
        }

        const api = window.electronAPI.menu;

        api.onNewFile(() => this.newFile());
        api.onOpenFile((filePath) => this.openFileByPath(filePath));
        api.onOpenFolder((folderPath) => this.openFolderByPath(folderPath));
        api.onSave(() => this.saveCurrentFile());
        api.onSaveAs(() => this.saveCurrentFileAs());
        api.onFind(() => this.showFind());
        api.onReplace(() => this.showReplace());
        api.onToggleSidebar(() => this.toggleSidebar());
        api.onToggleTerminal(() => this.toggleBottomPanel());
        api.onTogglePreview(() => this.togglePreview());
        api.onNewTerminal(() => this.terminalManager.createTerminal());
        api.onSplitTerminal(() => this.terminalManager.splitTerminal());
        api.onKillTerminal(() => this.terminalManager.killActiveTerminal());
        api.onExtensions(() => this.sidebarManager.showExtensions());
        api.onInstallExtension(() => this.showInstallExtension());
        api.onDocumentation(() => this.showDocumentation());
        api.onAbout(() => this.showAbout());
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            const ctrl = e.ctrlKey || e.metaKey;
            const shift = e.shiftKey;

            // Ctrl+N - New File
            if (ctrl && !shift && e.key === 'n') {
                e.preventDefault();
                this.newFile();
            }

            // Ctrl+O - Open File
            if (ctrl && !shift && e.key === 'o') {
                e.preventDefault();
                this.openFile();
            }

            // Ctrl+S - Save
            if (ctrl && !shift && e.key === 's') {
                e.preventDefault();
                this.saveCurrentFile();
            }

            // Ctrl+Shift+S - Save As
            if (ctrl && shift && e.key.toLowerCase() === 's') {
                e.preventDefault();
                this.saveCurrentFileAs();
            }

            // Ctrl+Shift+P - Command Palette
            if (ctrl && shift && e.key.toLowerCase() === 'p') {
                e.preventDefault();
                this.toggleCommandPalette();
            }

            // Ctrl+B - Toggle Sidebar
            if (ctrl && !shift && e.key === 'b') {
                e.preventDefault();
                this.toggleSidebar();
            }

            // Ctrl+` - Toggle Terminal
            if (ctrl && e.key === '`') {
                e.preventDefault();
                this.toggleBottomPanel();
            }

            // Ctrl+W - Close Tab
            if (ctrl && !shift && e.key === 'w') {
                e.preventDefault();
                this.tabManager.closeActiveTab();
            }

            // Escape - Close modals
            if (e.key === 'Escape') {
                this.closeModals();
            }
        });
    }

    async newFile() {
        const fileName = 'Untitled-' + Date.now() + '.txt';
        this.editorManager.createEditor(fileName, '');
        this.tabManager.addTab(fileName, '');
        this.hideWelcomeScreen();
    }

    async openFile() {
        console.log('Open file clicked, isElectron:', this.isElectron);

        if (!this.isElectron) {
            alert('File opening is only available in desktop mode');
            return;
        }

        try {
            const filePath = await this.fileSystem.showOpenDialog();
            console.log('Selected file:', filePath);
            if (filePath) {
                await this.openFileByPath(filePath);
            }
        } catch (error) {
            console.error('Error in openFile:', error);
            alert('Error opening file: ' + error.message);
        }
    }

    async openFileByPath(filePath) {
        try {
            console.log('Opening file:', filePath);
            const content = await this.fileSystem.readFile(filePath);
            this.editorManager.createEditor(filePath, content);
            this.tabManager.addTab(filePath, content);
            this.currentFile = filePath;
            this.hideWelcomeScreen();
        } catch (error) {
            console.error('Error opening file:', error);
            alert('Error opening file: ' + error.message);
        }
    }

    async openFolder() {
        console.log('Open folder clicked, isElectron:', this.isElectron);

        if (!this.isElectron) {
            alert('Folder opening is only available in desktop mode');
            return;
        }

        try {
            const folderPath = await this.fileSystem.showOpenFolderDialog();
            console.log('Selected folder:', folderPath);
            if (folderPath) {
                await this.openFolderByPath(folderPath);
            }
        } catch (error) {
            console.error('Error in openFolder:', error);
            alert('Error opening folder: ' + error.message);
        }
    }

    async openFolderByPath(folderPath) {
        try {
            console.log('Opening folder:', folderPath);
            await this.fileSystem.openWorkspace(folderPath);
            this.sidebarManager.loadWorkspace(folderPath);
            this.hideWelcomeScreen();
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
            console.log('File saved:', this.currentFile);
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
                console.log('File saved as:', filePath);
            } catch (error) {
                console.error('Error saving file:', error);
                alert('Error saving file: ' + error.message);
            }
        }
    }

    toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('hidden');
        }
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

    togglePreview() {
        const preview = document.getElementById('previewContainer');
        if (preview) {
            if (preview.style.display === 'none' || !preview.style.display) {
                preview.style.display = 'flex';
            } else {
                preview.style.display = 'none';
            }
        }
    }

    refreshPreview() {
        const frame = document.getElementById('preview-frame');
        if (frame) {
            const content = this.editorManager.getCurrentContent();
            const blob = new Blob([content], { type: 'text/html' });
            frame.src = URL.createObjectURL(blob);
        }
    }

    toggleCommandPalette() {
        const palette = document.getElementById('commandPalette');
        if (palette) {
            if (palette.style.display === 'none' || !palette.style.display) {
                palette.style.display = 'flex';
                document.getElementById('commandInput')?.focus();
            } else {
                palette.style.display = 'none';
            }
        }
    }

    closeModals() {
        const palette = document.getElementById('commandPalette');
        if (palette) {
            palette.style.display = 'none';
        }
    }

    hideWelcomeScreen() {
        const welcome = document.getElementById('editorWelcome');
        if (welcome) {
            welcome.style.display = 'none';
        }
    }

    showFind() {
        alert('Find feature coming soon!');
    }

    showReplace() {
        alert('Replace feature coming soon!');
    }

    showSettings() {
        alert('Settings panel coming soon!');
    }

    showInstallExtension() {
        alert('Extension installer coming soon!');
    }

    showDocumentation() {
        alert('Documentation coming soon!');
    }

    showAbout() {
        alert('SafeCode IDE v1.0.0\\nFull-featured development environment for mobile and web');
    }
}
