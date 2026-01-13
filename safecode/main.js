/**
 * SafeCode IDE - Main Entry Point
 */

import './styles/main.css';
import fileSystem from './core/FileSystem.js';
import { EditorManager } from './components/EditorManager.js';
import { SidebarManager } from './components/SidebarManager.js';
import { TabManager } from './components/TabManager.js';
import { TerminalManager } from './components/TerminalManager.js';
import { BuildManager } from './components/BuildManager.js';

class SafeCodeIDE {
    constructor() {
        this.fileSystem = fileSystem;
        this.editorManager = null;
        this.sidebarManager = null;
        this.tabManager = null;
        this.terminalManager = null;
        this.buildManager = null; // Initialize BuildManager
        this.currentFile = null;
        this.isElectron = typeof window !== 'undefined' && window.electronAPI;

        this.init();
    }

    async init() {
        console.log('🚀 SafeCode IDE initializing...');

        // Initialize managers
        this.tabManager = new TabManager(this);
        this.editorManager = new EditorManager(this);
        this.sidebarManager = new SidebarManager(this);
        this.terminalManager = new TerminalManager(this);
        this.buildManager = new BuildManager(this); // Initialize BuildManager

        // Setup event listeners
        this.setupEventListeners();
        this.setupMenuListeners();
        this.setupPanelHandlers();

        // Initialize UI components();
        this.setupKeyboardShortcuts();

        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        console.log('✅ SafeCode IDE ready!');
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
        const btnSyncPreview = document.getElementById('btnSyncPreview');

        if (btnClosePreview) {
            btnClosePreview.addEventListener('click', () => this.togglePreview());
        }

        if (btnRefreshPreview) {
            btnRefreshPreview.addEventListener('click', () => this.refreshPreview());
        }

        if (btnSyncPreview) {
            btnSyncPreview.addEventListener('click', () => this.syncPreview());
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
        if (!this.isElectron) return;

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
            if (ctrl && shift && e.key === 's') {
                e.preventDefault();
                this.saveCurrentFileAs();
            }

            // Ctrl+Shift+P - Command Palette
            if (ctrl && shift && e.key === 'p') {
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
        const fileName = 'Untitled-' + Date.now();
        this.editorManager.createEditor(fileName, '');
        this.tabManager.addTab(fileName, '');
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
            this.sidebarManager.loadWorkspace(folderPath);
            this.hideWelcomeScreen();
        } catch (error) {
            console.error('Error opening folder:', error);
            alert('Error opening folder: ' + error.message);
        }
    }

    async saveCurrentFile() {
        if (!this.currentFile) {
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
                this.currentFile = filePath;
                this.tabManager.renameTab(this.tabManager.getActiveTab(), filePath);
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
        if (panel.style.display === 'none') {
            panel.style.display = 'flex';
            this.terminalManager.resizeTerminals();
        } else {
            panel.style.display = 'none';
        }
    }

    switchPanel(panelId) {
        // Update tabs
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.classList.toggle('active', tab.dataset.panel === panelId);
        });

        // Update views
        document.querySelectorAll('.panel-view').forEach(view => {
            view.classList.toggle('active', view.id === `panel-${panelId}`);
        });

        if (panelId === 'terminal') {
            this.terminalManager.resizeTerminals();
        }
    }

    setupPanelHandlers() {
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.onclick = () => this.switchPanel(tab.dataset.panel);
        });

        document.getElementById('btnClosePanel').onclick = () => this.toggleBottomPanel();
    }

    togglePreview() {
        const preview = document.getElementById('previewContainer');
        if (preview) {
            if (preview.style.display === 'none') {
                preview.style.display = 'flex';
            } else {
                preview.style.display = 'none';
            }
        }
    }

    refreshPreview() {
        const frame = document.getElementById('preview-frame');
        if (frame) {
            frame.src = frame.src;
            if (this.isElectron && window.electronAPI && window.electronAPI.preview) {
                window.electronAPI.preview.refresh();
            }
        }
    }

    syncPreview() {
        const frame = document.getElementById('preview-frame');
        if (frame && this.isElectron && window.electronAPI && window.electronAPI.preview) {
            window.electronAPI.preview.open(frame.src);
        } else if (!this.isElectron) {
            alert('External preview is only available in Desktop mode.');
        }
    }

    toggleCommandPalette() {
        const palette = document.getElementById('commandPalette');
        if (palette) {
            if (palette.style.display === 'none') {
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
        console.log('Show find');
        // TODO: Implement find dialog
    }

    showReplace() {
        console.log('Show replace');
        // TODO: Implement replace dialog
    }

    showSettings() {
        console.log('Show settings');
        // TODO: Implement settings panel
    }

    showInstallExtension() {
        console.log('Show install extension');
        // TODO: Implement extension installer
    }

    showDocumentation() {
        console.log('Show documentation');
        // TODO: Open documentation
    }

    showAbout() {
        alert('SafeCode IDE v1.0.0\nFull-featured development environment for mobile and web');
    }
}

// Initialize IDE when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.ide = new SafeCodeIDE();
    });
} else {
    window.ide = new SafeCodeIDE();
}

export default SafeCodeIDE;
