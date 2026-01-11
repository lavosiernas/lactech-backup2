/**
 * SafeCode IDE - Core Classes (Standalone)
 * All manager classes in one file for easy loading
 */

// ============================================================================
// FileSystemManager
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
        window.electronAPI.fs.onFileChanged((path) => {
            this.emit('fileChanged', path);
        });

        window.electronAPI.fs.onFileAdded((path) => {
            this.emit('fileAdded', path);
        });

        window.electronAPI.fs.onFileDeleted((path) => {
            this.emit('fileDeleted', path);
        });
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
        if (this.isElectron) {
            const result = await window.electronAPI.fs.readFile(filePath);
            if (result.success) {
                this.openFiles.set(filePath, result.content);
                return result.content;
            } else {
                throw new Error(result.error);
            }
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async writeFile(filePath, content) {
        if (this.isElectron) {
            const result = await window.electronAPI.fs.writeFile(filePath, content);
            if (result.success) {
                this.openFiles.set(filePath, content);
                return true;
            } else {
                throw new Error(result.error);
            }
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async readDirectory(dirPath) {
        if (this.isElectron) {
            const result = await window.electronAPI.fs.readDir(dirPath);
            if (result.success) {
                return result.items;
            } else {
                throw new Error(result.error);
            }
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async openWorkspace(dirPath) {
        if (this.isElectron) {
            this.currentWorkspace = dirPath;
            const result = await window.electronAPI.fs.watchDir(dirPath);
            if (!result.success) {
                throw new Error(result.error);
            }
            return await this.readDirectory(dirPath);
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async showOpenDialog() {
        if (this.isElectron) {
            const result = await window.electronAPI.dialog.openFile();
            if (!result.canceled && result.filePaths.length > 0) {
                return result.filePaths[0];
            }
            return null;
        } else {
            throw new Error('Dialog not available in web mode');
        }
    }

    async showOpenFolderDialog() {
        if (this.isElectron) {
            const result = await window.electronAPI.dialog.openDirectory();
            if (!result.canceled && result.filePaths.length > 0) {
                return result.filePaths[0];
            }
            return null;
        } else {
            throw new Error('Dialog not available in web mode');
        }
    }

    async showSaveDialog(defaultPath = '') {
        if (this.isElectron) {
            const result = await window.electronAPI.dialog.saveFile(defaultPath);
            if (!result.canceled) {
                return result.filePath;
            }
            return null;
        } else {
            throw new Error('Dialog not available in web mode');
        }
    }
}

// ============================================================================
// EditorManager
// ============================================================================
class EditorManager {
    constructor(ide) {
        this.ide = ide;
        this.editors = new Map();
        this.activeEditor = null;
    }

    createEditor(filePath, content = '') {
        const container = document.getElementById('editorContainer');
        const welcome = document.getElementById('editorWelcome');
        if (welcome) welcome.style.display = 'none';

        // Create simple textarea editor (CodeMirror will be added later)
        const editorWrapper = document.createElement('div');
        editorWrapper.className = 'editor-instance';
        editorWrapper.dataset.file = filePath;
        editorWrapper.style.height = '100%';

        const textarea = document.createElement('textarea');
        textarea.style.width = '100%';
        textarea.style.height = '100%';
        textarea.style.background = '#1e1e1e';
        textarea.style.color = '#d4d4d4';
        textarea.style.border = 'none';
        textarea.style.outline = 'none';
        textarea.style.padding = '1rem';
        textarea.style.fontFamily = 'JetBrains Mono, monospace';
        textarea.style.fontSize = '14px';
        textarea.style.lineHeight = '1.6';
        textarea.style.resize = 'none';
        textarea.value = content;

        textarea.addEventListener('input', () => {
            this.onEditorChange(filePath);
        });

        editorWrapper.appendChild(textarea);
        container.appendChild(editorWrapper);

        this.editors.set(filePath, textarea);
        this.activeEditor = textarea;
        this.showEditor(filePath);

        return textarea;
    }

    showEditor(filePath) {
        const allEditors = document.querySelectorAll('.editor-instance');
        allEditors.forEach(editor => {
            editor.style.display = 'none';
        });

        const editorWrapper = document.querySelector(`[data-file="${filePath}"]`);
        if (editorWrapper) {
            editorWrapper.style.display = 'block';
            const textarea = this.editors.get(filePath);
            if (textarea) {
                this.activeEditor = textarea;
                textarea.focus();
            }
        }
    }

    closeEditor(filePath) {
        const textarea = this.editors.get(filePath);
        if (textarea) {
            this.editors.delete(filePath);
        }

        const editorWrapper = document.querySelector(`[data-file="${filePath}"]`);
        if (editorWrapper) {
            editorWrapper.remove();
        }

        if (this.editors.size === 0) {
            const welcome = document.getElementById('editorWelcome');
            if (welcome) welcome.style.display = 'flex';
            this.activeEditor = null;
        }
    }

    getCurrentContent() {
        if (this.activeEditor) {
            return this.activeEditor.value;
        }
        return '';
    }

    setContent(filePath, content) {
        const textarea = this.editors.get(filePath);
        if (textarea) {
            textarea.value = content;
        }
    }

    onEditorChange(filePath) {
        this.ide.tabManager.markDirty(filePath);
    }

    async reloadFile(filePath) {
        if (this.editors.has(filePath)) {
            try {
                const content = await this.ide.fileSystem.readFile(filePath);
                this.setContent(filePath, content);
                this.ide.tabManager.markSaved(filePath);
            } catch (error) {
                console.error('Error reloading file:', error);
            }
        }
    }
}

// ============================================================================
// TabManager
// ============================================================================
class TabManager {
    constructor(ide) {
        this.ide = ide;
        this.tabs = new Map();
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
        if (this.tabs.has(filePath)) {
            this.activateTab(filePath);
            return;
        }

        const tabsContainer = document.getElementById('tabsContainer');
        if (!tabsContainer) return;

        const tab = document.createElement('div');
        tab.className = 'editor-tab active';
        tab.dataset.file = filePath;

        const fileName = this.getFileName(filePath);
        tab.innerHTML = `
            <i data-lucide="file" class="w-3 h-3"></i>
            <span class="tab-label">${fileName}</span>
            <button class="tab-close">
                <i data-lucide="x" class="w-3 h-3"></i>
            </button>
        `;

        this.deactivateAllTabs();
        tabsContainer.appendChild(tab);
        this.tabs.set(filePath, tab);
        this.activeTab = filePath;

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
        this.tabs.forEach(tab => tab.classList.remove('active'));
    }

    closeTab(filePath) {
        const tab = this.tabs.get(filePath);
        if (!tab) return;

        if (tab.classList.contains('dirty')) {
            const confirm = window.confirm(`Save changes to ${this.getFileName(filePath)}?`);
            if (confirm) {
                this.ide.saveCurrentFile();
            }
        }

        tab.remove();
        this.tabs.delete(filePath);
        this.ide.editorManager.closeEditor(filePath);

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
}

// ============================================================================
// SidebarManager
// ============================================================================
class SidebarManager {
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
        const tabs = document.querySelectorAll('.sidebar-tab');
        tabs.forEach(tab => {
            tab.classList.toggle('active', tab.dataset.view === view);
        });
        this.currentView = view;

        switch (view) {
            case 'explorer': this.showExplorer(); break;
            case 'search': this.showSearch(); break;
            case 'git': this.showGit(); break;
            case 'extensions': this.showExtensions(); break;
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
            fileTree.innerHTML = this.renderFileTree(items);
            this.setupFileTreeHandlers();
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Error loading file tree:', error);
            fileTree.innerHTML = `<div class="error">Error loading files</div>`;
        }
    }

    renderFileTree(items) {
        const sorted = items.sort((a, b) => {
            if (a.isDirectory && !b.isDirectory) return -1;
            if (!a.isDirectory && b.isDirectory) return 1;
            return a.name.localeCompare(b.name);
        });

        return sorted.map(item => {
            const icon = item.isDirectory ? 'folder' : 'file';
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
                if (!isFolder) {
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
                <div class="section-header"><span>SEARCH</span></div>
                <div class="empty-state"><p>Search coming soon</p></div>
            </div>
        `;
    }

    showGit() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;
        content.innerHTML = `
            <div class="sidebar-section">
                <div class="section-header"><span>SOURCE CONTROL</span></div>
                <div class="empty-state"><p>Git integration coming soon</p></div>
            </div>
        `;
    }

    showExtensions() {
        const content = document.getElementById('sidebarContent');
        if (!content) return;
        content.innerHTML = `
            <div class="sidebar-section">
                <div class="section-header"><span>EXTENSIONS</span></div>
                <div class="empty-state"><p>Extension marketplace coming soon</p></div>
            </div>
        `;
    }
}

// ============================================================================
// TerminalManager
// ============================================================================
class TerminalManager {
    constructor(ide) {
        this.ide = ide;
        this.terminals = new Map();
        this.activeTerminal = null;
        this.terminalCounter = 0;
    }

    createTerminal() {
        const terminalId = `terminal-${this.terminalCounter++}`;
        const panelContent = document.getElementById('panelContent');
        if (!panelContent) return;

        const terminalContainer = document.createElement('div');
        terminalContainer.className = 'terminal-instance';
        terminalContainer.id = terminalId;
        terminalContainer.style.height = '100%';
        terminalContainer.style.padding = '1rem';
        terminalContainer.style.fontFamily = 'JetBrains Mono, monospace';
        terminalContainer.style.fontSize = '14px';
        terminalContainer.style.color = '#e4e4e7';
        terminalContainer.style.overflow = 'auto';

        // Check if xterm is available
        if (typeof Terminal !== 'undefined') {
            try {
                const terminal = new Terminal({
                    cursorBlink: true,
                    fontSize: 13,
                    fontFamily: 'JetBrains Mono, monospace',
                    theme: {
                        background: '#000000',
                        foreground: '#e4e4e7'
                    }
                });

                terminal.open(terminalContainer);
                terminal.writeln('\x1b[1;35mSafeCode IDE Terminal\x1b[0m');
                terminal.writeln('Type commands here...\n');

                this.setupWebTerminal(terminal);
                this.terminals.set(terminalId, { terminal, element: terminalContainer });
            } catch (error) {
                console.error('Error creating xterm terminal:', error);
                this.createSimpleTerminal(terminalContainer, terminalId);
            }
        } else {
            this.createSimpleTerminal(terminalContainer, terminalId);
        }

        panelContent.appendChild(terminalContainer);
        this.activeTerminal = terminalId;

        const panel = document.getElementById('bottomPanel');
        if (panel) panel.style.display = 'flex';

        return terminalId;
    }

    createSimpleTerminal(container, terminalId) {
        container.innerHTML = `
            <div style="color: #8b5cf6; font-weight: bold; margin-bottom: 0.5rem;">SafeCode IDE Terminal</div>
            <div style="margin-bottom: 1rem;">Simple terminal mode (xterm.js not loaded)</div>
            <div style="color: #71717a;">Terminal functionality will be available when xterm.js loads properly.</div>
        `;
        this.terminals.set(terminalId, { terminal: null, element: container });
    }

    setupWebTerminal(terminal) {
        let currentLine = '';
        const prompt = '\x1b[1;32m$\x1b[0m ';
        terminal.write(prompt);

        terminal.onData(data => {
            switch (data) {
                case '\r':
                    terminal.write('\r\n');
                    this.executeCommand(terminal, currentLine);
                    currentLine = '';
                    terminal.write(prompt);
                    break;
                case '\u007F':
                    if (currentLine.length > 0) {
                        currentLine = currentLine.slice(0, -1);
                        terminal.write('\b \b');
                    }
                    break;
                default:
                    currentLine += data;
                    terminal.write(data);
            }
        });
    }

    executeCommand(terminal, command) {
        const cmd = command.trim();
        if (!cmd) return;

        switch (cmd) {
            case 'clear':
                terminal.clear();
                break;
            case 'help':
                terminal.writeln('Available commands:');
                terminal.writeln('  clear  - Clear terminal');
                terminal.writeln('  help   - Show this help');
                terminal.writeln('  date   - Show current date/time');
                terminal.writeln('  echo   - Echo text');
                break;
            case 'date':
                terminal.writeln(new Date().toString());
                break;
            default:
                if (cmd.startsWith('echo ')) {
                    terminal.writeln(cmd.substring(5));
                } else {
                    terminal.writeln(`\x1b[1;31mCommand not found:\x1b[0m ${cmd}`);
                }
        }
    }

    splitTerminal() {
        this.createTerminal();
    }

    killActiveTerminal() {
        if (this.activeTerminal) {
            this.killTerminal(this.activeTerminal);
        }
    }

    killTerminal(terminalId) {
        const terminalData = this.terminals.get(terminalId);
        if (terminalData) {
            if (terminalData.terminal) {
                terminalData.terminal.dispose();
            }
            terminalData.element.remove();
            this.terminals.delete(terminalId);

            if (this.terminals.size === 0) {
                const panel = document.getElementById('bottomPanel');
                if (panel) panel.style.display = 'none';
                this.activeTerminal = null;
            } else {
                this.activeTerminal = Array.from(this.terminals.keys())[0];
            }
        }
    }
}
