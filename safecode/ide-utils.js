/**
 * SafeCode IDE - Utility Classes
 * FileSystemManager and CommandPalette
 */

// ============================================================================
// FileSystemManager (Same as before but included here)
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
        window.electronAPI.fs.onFileChanged((path) => this.emit('fileChanged', path));
        window.electronAPI.fs.onFileAdded((path) => this.emit('fileAdded', path));
        window.electronAPI.fs.onFileDeleted((path) => this.emit('fileDeleted', path));
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
// Command Palette
// ============================================================================
class CommandPalette {
    constructor(ide) {
        this.ide = ide;
        this.isOpen = false;
        this.selectedIndex = 0;
        this.filteredCommands = [];

        this.commands = [
            {
                id: 'file.new',
                title: 'New File',
                description: 'Create a new file',
                icon: 'file-plus',
                shortcut: ['Ctrl', 'N'],
                action: () => this.ide.newFile()
            },
            {
                id: 'file.open',
                title: 'Open File',
                description: 'Open an existing file',
                icon: 'folder-open',
                shortcut: ['Ctrl', 'O'],
                action: () => this.ide.openFile()
            },
            {
                id: 'file.openFolder',
                title: 'Open Folder',
                description: 'Open a folder as workspace',
                icon: 'folder',
                shortcut: ['Ctrl', 'Shift', 'O'],
                action: () => this.ide.openFolder()
            },
            {
                id: 'file.save',
                title: 'Save',
                description: 'Save the current file',
                icon: 'save',
                shortcut: ['Ctrl', 'S'],
                action: () => this.ide.saveCurrentFile()
            },
            {
                id: 'file.saveAs',
                title: 'Save As',
                description: 'Save the current file with a new name',
                icon: 'save',
                shortcut: ['Ctrl', 'Shift', 'S'],
                action: () => this.ide.saveCurrentFileAs()
            },
            {
                id: 'view.toggleSidebar',
                title: 'Toggle Sidebar',
                description: 'Show or hide the sidebar',
                icon: 'sidebar',
                shortcut: ['Ctrl', 'B'],
                action: () => this.ide.toggleSidebar()
            },
            {
                id: 'view.toggleTerminal',
                title: 'Toggle Terminal',
                description: 'Show or hide the terminal',
                icon: 'terminal',
                shortcut: ['Ctrl', '`'],
                action: () => this.ide.toggleBottomPanel()
            },
            {
                id: 'view.togglePreview',
                title: 'Toggle Preview',
                description: 'Show or hide the live preview',
                icon: 'eye',
                shortcut: ['Ctrl', 'Shift', 'V'],
                action: () => this.ide.previewManager.toggle()
            },
            {
                id: 'server.goLive',
                title: 'Go Live',
                description: 'Start or stop the live server',
                icon: 'radio',
                shortcut: [],
                action: () => this.ide.toggleLiveServer()
            },
            {
                id: 'terminal.new',
                title: 'New Terminal',
                description: 'Create a new terminal instance',
                icon: 'plus',
                shortcut: ['Ctrl', 'Shift', '`'],
                action: () => this.ide.terminalManager.createTerminal()
            },
            {
                id: 'editor.closeTab',
                title: 'Close Tab',
                description: 'Close the active editor tab',
                icon: 'x',
                shortcut: ['Ctrl', 'W'],
                action: () => this.ide.tabManager.closeActiveTab()
            },
            {
                id: 'editor.closeAll',
                title: 'Close All Tabs',
                description: 'Close all open editor tabs',
                icon: 'x-circle',
                shortcut: [],
                action: () => this.ide.tabManager.closeAll()
            }
        ];

        this.setupEventListeners();
    }

    setupEventListeners() {
        const input = document.getElementById('commandInput');
        const palette = document.getElementById('commandPalette');

        if (input) {
            input.addEventListener('input', (e) => {
                this.filterCommands(e.target.value);
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    this.selectNext();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    this.selectPrevious();
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    this.executeSelected();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    this.close();
                }
            });
        }

        if (palette) {
            palette.addEventListener('click', (e) => {
                if (e.target === palette) {
                    this.close();
                }
            });
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        const palette = document.getElementById('commandPalette');
        const input = document.getElementById('commandInput');

        if (palette) {
            this.isOpen = true;
            palette.style.display = 'flex';

            if (input) {
                input.value = '';
                input.focus();
            }

            this.filterCommands('');
        }
    }

    close() {
        const palette = document.getElementById('commandPalette');
        if (palette) {
            this.isOpen = false;
            palette.style.display = 'none';
        }
    }

    filterCommands(query) {
        const lowerQuery = query.toLowerCase();

        // Get core commands
        let allCommands = [...this.commands];

        // Add extension commands
        if (this.ide.extensionManager) {
            this.ide.extensionManager.commands.forEach((cmd, id) => {
                allCommands.push({
                    id: id,
                    title: cmd.title,
                    description: `Extension: ${cmd.extensionId} - ${cmd.description}`,
                    icon: cmd.icon,
                    shortcut: [],
                    isExtension: true,
                    action: () => this.ide.extensionManager.executeCommand(id)
                });
            });
        }

        if (!query) {
            this.filteredCommands = allCommands;
        } else {
            this.filteredCommands = allCommands.filter(cmd =>
                cmd.title.toLowerCase().includes(lowerQuery) ||
                cmd.description.toLowerCase().includes(lowerQuery) ||
                cmd.id.toLowerCase().includes(lowerQuery)
            );
        }

        this.selectedIndex = 0;
        this.renderCommands();
    }

    renderCommands() {
        const list = document.getElementById('commandList');
        if (!list) return;

        if (this.filteredCommands.length === 0) {
            list.innerHTML = '<div class="empty-state"><p>No commands found</p></div>';
            return;
        }

        list.innerHTML = this.filteredCommands.map((cmd, index) => `
            <div class="command-item ${index === this.selectedIndex ? 'selected' : ''}" data-index="${index}">
                <div class="command-item-left">
                    <div class="command-item-icon">
                        <i data-lucide="${cmd.icon}" class="w-4 h-4"></i>
                    </div>
                    <div class="command-item-text">
                        <div class="command-item-title">${cmd.title}</div>
                        <div class="command-item-description">${cmd.description}</div>
                    </div>
                </div>
                ${cmd.shortcut.length > 0 ? `
                    <div class="command-item-shortcut">
                        ${cmd.shortcut.map(key => `<kbd>${key}</kbd>`).join(' + ')}
                    </div>
                ` : ''}
            </div>
        `).join('');

        // Add click handlers
        list.querySelectorAll('.command-item').forEach(item => {
            item.addEventListener('click', () => {
                const index = parseInt(item.dataset.index);
                this.selectedIndex = index;
                this.executeSelected();
            });
        });

        lucide.createIcons();
    }

    selectNext() {
        if (this.selectedIndex < this.filteredCommands.length - 1) {
            this.selectedIndex++;
            this.renderCommands();
            this.scrollToSelected();
        }
    }

    selectPrevious() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
            this.renderCommands();
            this.scrollToSelected();
        }
    }

    scrollToSelected() {
        const list = document.getElementById('commandList');
        const selected = list?.querySelector('.command-item.selected');
        if (selected) {
            selected.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    executeSelected() {
        const command = this.filteredCommands[this.selectedIndex];
        if (command) {
            this.close();
            command.action();
        }
    }
}
