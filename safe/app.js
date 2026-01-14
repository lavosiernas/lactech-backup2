/**
 * SafeCode IDE - Versão Refatorada do Zero
 * Arquivo principal consolidado e limpo
 */

// ============================================================================
// INITIALIZATION
// ============================================================================

let ide = null;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIDE);
} else {
    initIDE();
}

async function initIDE() {
    console.log('🚀 SafeCode IDE - Inicializando...');
    
    try {
        // Initialize Monaco Editor
        await initMonaco();
        
        // Create IDE instance
        ide = new SafeCodeIDE();
        window.ide = ide;
        
        console.log('✅ SafeCode IDE - Pronto!');
    } catch (error) {
        console.error('❌ Erro ao inicializar IDE:', error);
    }
}

async function initMonaco() {
    return new Promise((resolve) => {
        if (typeof require === 'undefined') {
            console.warn('Monaco Editor não disponível, usando fallback');
            resolve();
            return;
        }

        require.config({
            paths: {
                'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs'
            }
        });

        require(['vs/editor/editor.main'], function () {
            console.log('✅ Monaco Editor carregado');
            resolve();
        });
    });
}

// ============================================================================
// FILE SYSTEM MANAGER
// ============================================================================

class FileSystemManager {
    constructor() {
        this.isElectron = typeof window !== 'undefined' && window.electronAPI;
        this.currentWorkspace = null;
        this.openFiles = new Map();
        this.eventHandlers = {
            fileChanged: [],
            fileAdded: [],
            fileDeleted: []
        };

        if (this.isElectron) {
            this.setupElectronListeners();
        }
    }

    setupElectronListeners() {
        if (window.electronAPI && window.electronAPI.fs) {
            window.electronAPI.fs.onFileChanged((path) => this.emit('fileChanged', path));
            window.electronAPI.fs.onFileAdded((path) => this.emit('fileAdded', path));
            window.electronAPI.fs.onFileDeleted((path) => this.emit('fileDeleted', path));
        }
    }

    on(event, handler) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event].push(handler);
        }
    }

    emit(event, ...args) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event].forEach(handler => handler(...args));
        }
    }

    async readFile(filePath) {
        if (this.isElectron && window.electronAPI && window.electronAPI.fs) {
            const result = await window.electronAPI.fs.readFile(filePath);
            if (result.success) {
                this.openFiles.set(filePath, result.content);
                return result.content;
            } else {
                throw new Error(result.error);
            }
        } else {
            // Web mode - placeholder
            return '';
        }
    }

    async writeFile(filePath, content) {
        if (this.isElectron && window.electronAPI && window.electronAPI.fs) {
            const result = await window.electronAPI.fs.writeFile(filePath, content);
            if (result.success) {
                this.openFiles.set(filePath, content);
                return true;
            } else {
                throw new Error(result.error);
            }
        } else {
            // Web mode - placeholder
            return false;
        }
    }

    async readDirectory(dirPath) {
        if (this.isElectron && window.electronAPI && window.electronAPI.fs) {
            const result = await window.electronAPI.fs.readDir(dirPath);
            if (result.success) {
                return result.items || [];
            } else {
                throw new Error(result.error);
            }
        } else {
            // Web mode - return empty array
            return [];
        }
    }
}

// ============================================================================
// MAIN IDE CLASS
// ============================================================================

class SafeCodeIDE {
    constructor() {
        this.isElectron = typeof window !== 'undefined' && window.electronAPI;
        this.workspace = null;
        this.currentFile = null;
        
        // Initialize file system
        this.fileSystem = new FileSystemManager();
        
        // Initialize managers
        this.terminalManager = new TerminalManager(this);
        this.gitManager = new GitManager(this);
        this.editorManager = new EditorManager(this);
        this.sidebarManager = new SidebarManager(this);
        
        // DOM References
        this.bottomPanel = document.getElementById('bottomPanel');
        this.editorWelcome = document.getElementById('editorWelcome');
        
        // Initialize
        this.init();
    }
    
    async init() {
        this.setupEventListeners();
        this.setupPanelHandlers();
        this.initializeLucideIcons();
    }
    
    setupEventListeners() {
        // Terminal toggle
        document.getElementById('menuItemToggleTerminal')?.addEventListener('click', () => this.toggleBottomPanel());
        document.getElementById('btnNewTerminalPanel')?.addEventListener('click', () => {
            if (this.bottomPanel) {
                this.bottomPanel.classList.add('open');
                this.switchPanel('terminal');
            }
            this.terminalManager.createTerminal();
        });
        document.getElementById('btnClosePanel')?.addEventListener('click', () => this.toggleBottomPanel());
        
        // Panel tabs
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const panelId = tab.dataset.panel;
                this.switchPanel(panelId);
            });
        });
        
        // Sidebar tabs
        document.querySelectorAll('.sidebar-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const view = tab.dataset.view;
                this.sidebarManager.switchView(view);
            });
        });
    }
    
    setupPanelHandlers() {
        // Panel tabs
        document.querySelectorAll('.panel-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const panelId = tab.dataset.panel;
                this.switchPanel(panelId);
            });
        });
    }
    
    toggleBottomPanel() {
        if (!this.bottomPanel) return;
        
        const isOpen = this.bottomPanel.classList.contains('open');
        
        if (isOpen) {
            this.bottomPanel.classList.remove('open');
        } else {
            this.bottomPanel.classList.add('open');
            if (this.terminalManager && this.terminalManager.terminals.size === 0) {
                setTimeout(() => {
                    this.terminalManager.createTerminal();
                }, 100);
            }
        }
    }
    
    switchPanel(panelId) {
        const panel = document.getElementById('bottomPanel');
        if (panel) {
            panel.classList.add('open');
            
            // Update tabs
            document.querySelectorAll('.panel-tab').forEach(tab => {
                if (tab.dataset.panel === panelId) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
            
            // Update views
            document.querySelectorAll('.panel-view').forEach(view => {
                if (view.id === `panel-${panelId}`) {
                    view.classList.add('active');
                } else {
                    view.classList.remove('active');
                }
            });
            
            // Resize terminal if needed
            if (panelId === 'terminal' && this.terminalManager) {
                setTimeout(() => {
                    if (typeof this.terminalManager.resizeTerminals === 'function') {
                        this.terminalManager.resizeTerminals();
                    }
                }, 350);
            }
        }
    }
    
    initializeLucideIcons() {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}
