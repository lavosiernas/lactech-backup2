const { contextBridge, ipcRenderer } = require('electron');

// Expose protected methods that allow the renderer process to use
// the ipcRenderer without exposing the entire object
contextBridge.exposeInMainWorld('electronAPI', {
    // File System API
    fs: {
        readFile: (filePath) => ipcRenderer.invoke('fs:readFile', filePath),
        writeFile: (filePath, content) => ipcRenderer.invoke('fs:writeFile', filePath, content),
        readDir: (dirPath) => ipcRenderer.invoke('fs:readDir', dirPath),
        createFile: (filePath) => ipcRenderer.invoke('fs:createFile', filePath),
        createDir: (dirPath) => ipcRenderer.invoke('fs:createDir', dirPath),
        delete: (targetPath) => ipcRenderer.invoke('fs:delete', targetPath),
        rename: (oldPath, newPath) => ipcRenderer.invoke('fs:rename', oldPath, newPath),
        watchDir: (dirPath) => ipcRenderer.invoke('fs:watchDir', dirPath),

        // File system events
        onFileAdded: (callback) => ipcRenderer.on('fs:file-added', (event, path) => callback(path)),
        onFileChanged: (callback) => ipcRenderer.on('fs:file-changed', (event, path) => callback(path)),
        onFileDeleted: (callback) => ipcRenderer.on('fs:file-deleted', (event, path) => callback(path)),
        onDirAdded: (callback) => ipcRenderer.on('fs:dir-added', (event, path) => callback(path)),
        onDirDeleted: (callback) => ipcRenderer.on('fs:dir-deleted', (event, path) => callback(path))
    },

    // Dialog API
    dialog: {
        openFile: () => ipcRenderer.invoke('dialog:openFile'),
        openDirectory: () => ipcRenderer.invoke('dialog:openDirectory'),
        saveFile: (defaultPath) => ipcRenderer.invoke('dialog:saveFile', defaultPath)
    },

    // Git API
    git: {
        status: (cwd) => ipcRenderer.invoke('git:status', cwd),
        stage: (cwd, filePath) => ipcRenderer.invoke('git:stage', cwd, filePath),
        unstage: (cwd, filePath) => ipcRenderer.invoke('git:unstage', cwd, filePath),
        commit: (cwd, message) => ipcRenderer.invoke('git:commit', cwd, message),
        diff: (cwd, filePath) => ipcRenderer.invoke('git:diff', cwd, filePath)
    },

    // Menu events
    menu: {
        onNewFile: (callback) => ipcRenderer.on('menu-new-file', callback),
        onOpenFile: (callback) => ipcRenderer.on('menu-open-file', (event, filePath) => callback(filePath)),
        onOpenFolder: (callback) => ipcRenderer.on('menu-open-folder', (event, folderPath) => callback(folderPath)),
        onSave: (callback) => ipcRenderer.on('menu-save', callback),
        onSaveAs: (callback) => ipcRenderer.on('menu-save-as', callback),
        onFind: (callback) => ipcRenderer.on('menu-find', callback),
        onReplace: (callback) => ipcRenderer.on('menu-replace', callback),
        onToggleSidebar: (callback) => ipcRenderer.on('menu-toggle-sidebar', callback),
        onToggleTerminal: (callback) => ipcRenderer.on('menu-toggle-terminal', callback),
        onTogglePreview: (callback) => ipcRenderer.on('menu-toggle-preview', callback),
        onNewTerminal: (callback) => ipcRenderer.on('menu-new-terminal', callback),
        onSplitTerminal: (callback) => ipcRenderer.on('menu-split-terminal', callback),
        onKillTerminal: (callback) => ipcRenderer.on('menu-kill-terminal', callback),
        onExtensions: (callback) => ipcRenderer.on('menu-extensions', callback),
        onInstallExtension: (callback) => ipcRenderer.on('menu-install-extension', callback),
        onDocumentation: (callback) => ipcRenderer.on('menu-documentation', callback),
        onAbout: (callback) => ipcRenderer.on('menu-about', callback)
    },

    // Terminal API
    terminal: {
        create: (id) => ipcRenderer.invoke('terminal:create', id),
        write: (id, data) => ipcRenderer.invoke('terminal:write', id, data),
        resize: (id, cols, rows) => ipcRenderer.invoke('terminal:resize', id, cols, rows),
        kill: (id) => ipcRenderer.invoke('terminal:kill', id),
        onData: (id, callback) => ipcRenderer.on(`terminal:data-${id}`, (event, data) => callback(data))
    },

    // Preview API
    preview: {
        open: (url) => ipcRenderer.invoke('preview:open', url),
        refresh: () => ipcRenderer.invoke('preview:refresh')
    },

    // Extensions API
    extensions: {
        list: () => ipcRenderer.invoke('extensions:list'),
        readFile: (extensionId, fileName) => ipcRenderer.invoke('extensions:readFile', extensionId, fileName)
    },

    // Platform info
    platform: process.platform,
    isElectron: true
});
