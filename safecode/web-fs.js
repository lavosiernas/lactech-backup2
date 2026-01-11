/**
 * SafeCode Web File System Manager
 * Uses LocalStorage to simulate a persistent file system in the browser.
 */
class WebFileSystemManager {
    constructor() {
        this.storageKey = 'safecode_workspace';
        this.eventHandlers = {
            fileChanged: [],
            fileAdded: [],
            fileDeleted: []
        };
        this.initStorage();
    }

    initStorage() {
        if (!localStorage.getItem(this.storageKey)) {
            const defaultWorkspace = {
                'index.html': '<!DOCTYPE html>\n<html>\n<head>\n  <title>SafeCode Web</title>\n</head>\n<body>\n  <h1>Welcome to SafeCode Web Edition</h1>\n</body>\n</html>',
                'styles.css': 'body { background: #000; color: #fff; font-family: sans-serif; }',
                'script.js': 'console.log("SafeCode Web is Ready!");'
            };
            localStorage.setItem(this.storageKey, JSON.stringify(defaultWorkspace));
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

    getWorkspace() {
        return JSON.parse(localStorage.getItem(this.storageKey)) || {};
    }

    saveWorkspace(workspace) {
        localStorage.setItem(this.storageKey, JSON.stringify(workspace));
    }

    async readFile(filePath) {
        const workspace = this.getWorkspace();
        if (workspace.hasOwnProperty(filePath)) {
            return workspace[filePath];
        }
        throw new Error(`File not found: ${filePath}`);
    }

    async writeFile(filePath, content) {
        const workspace = this.getWorkspace();
        const isNew = !workspace.hasOwnProperty(filePath);
        workspace[filePath] = content;
        this.saveWorkspace(workspace);

        if (isNew) {
            this.emit('fileAdded', filePath);
        } else {
            this.emit('fileChanged', filePath);
        }
        return true;
    }

    async readDirectory(dirPath = '') {
        const workspace = this.getWorkspace();
        return Object.keys(workspace).map(name => ({
            name: name,
            path: name,
            isDirectory: false
        }));
    }

    async openWorkspace(dirPath) {
        return this.readDirectory();
    }

    // Web Fallbacks for Dialogs
    async showOpenDialog() {
        return prompt('Simulado: Digite o nome do arquivo para abrir (ex: index.html):');
    }

    async showOpenFolderDialog() {
        return 'root';
    }

    async showSaveDialog(defaultPath = '') {
        return prompt('Simulado: Salvar arquivo como:', defaultPath);
    }
}
