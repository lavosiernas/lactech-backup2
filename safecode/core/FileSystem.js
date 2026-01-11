/**
 * FileSystem Manager
 * Handles all file system operations with support for both Electron and Web environments
 */

class FileSystem {
    constructor() {
        this.isElectron = typeof window !== 'undefined' && window.electronAPI;
        this.currentWorkspace = null;
        this.openFiles = new Map(); // Map of filePath -> fileContent
        this.fileWatchers = new Map();
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
            // Web fallback - use File System Access API if available
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

    async createFile(filePath) {
        if (this.isElectron) {
            const result = await window.electronAPI.fs.createFile(filePath);
            if (!result.success) {
                throw new Error(result.error);
            }
            return true;
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async createDirectory(dirPath) {
        if (this.isElectron) {
            const result = await window.electronAPI.fs.createDir(dirPath);
            if (!result.success) {
                throw new Error(result.error);
            }
            return true;
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async delete(targetPath) {
        if (this.isElectron) {
            const result = await window.electronAPI.fs.delete(targetPath);
            if (!result.success) {
                throw new Error(result.error);
            }
            this.openFiles.delete(targetPath);
            return true;
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async rename(oldPath, newPath) {
        if (this.isElectron) {
            const result = await window.electronAPI.fs.rename(oldPath, newPath);
            if (!result.success) {
                throw new Error(result.error);
            }

            // Update open files map
            if (this.openFiles.has(oldPath)) {
                const content = this.openFiles.get(oldPath);
                this.openFiles.delete(oldPath);
                this.openFiles.set(newPath, content);
            }

            return true;
        } else {
            throw new Error('File System Access not available in web mode');
        }
    }

    async openWorkspace(dirPath) {
        if (this.isElectron) {
            this.currentWorkspace = dirPath;

            // Start watching the directory
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

    getOpenFile(filePath) {
        return this.openFiles.get(filePath);
    }

    isFileOpen(filePath) {
        return this.openFiles.has(filePath);
    }

    closeFile(filePath) {
        this.openFiles.delete(filePath);
    }

    getOpenFiles() {
        return Array.from(this.openFiles.keys());
    }

    getCurrentWorkspace() {
        return this.currentWorkspace;
    }

    // Utility: Get file extension
    getFileExtension(filePath) {
        const parts = filePath.split('.');
        return parts.length > 1 ? parts[parts.length - 1].toLowerCase() : '';
    }

    // Utility: Get file name from path
    getFileName(filePath) {
        const parts = filePath.split(/[/\\]/);
        return parts[parts.length - 1];
    }

    // Utility: Get directory from path
    getDirectory(filePath) {
        const parts = filePath.split(/[/\\]/);
        parts.pop();
        return parts.join('/');
    }
}

// Export singleton instance
const fileSystem = new FileSystem();
export default fileSystem;
