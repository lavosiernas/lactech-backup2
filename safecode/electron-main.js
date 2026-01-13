const { app, BrowserWindow, ipcMain, Menu, dialog } = require('electron');
const path = require('path');
const fs = require('fs').promises;
const chokidar = require('chokidar');
const { exec } = require('child_process');
const util = require('util');
const execPromise = util.promisify(exec);

let mainWindow;
let fileWatcher;

// Configuração do menu da aplicação
function createMenu() {
  const template = [
    {
      label: 'File',
      submenu: [
        {
          label: 'New File',
          accelerator: 'CmdOrCtrl+N',
          click: () => mainWindow.webContents.send('menu-new-file')
        },
        {
          label: 'Open File',
          accelerator: 'CmdOrCtrl+O',
          click: async () => {
            const result = await dialog.showOpenDialog(mainWindow, {
              properties: ['openFile'],
              filters: [
                { name: 'All Files', extensions: ['*'] },
                { name: 'JavaScript', extensions: ['js', 'jsx', 'ts', 'tsx'] },
                { name: 'HTML', extensions: ['html', 'htm'] },
                { name: 'CSS', extensions: ['css', 'scss', 'sass'] },
                { name: 'JSON', extensions: ['json'] }
              ]
            });
            if (!result.canceled) {
              mainWindow.webContents.send('menu-open-file', result.filePaths[0]);
            }
          }
        },
        {
          label: 'Open Folder',
          accelerator: 'CmdOrCtrl+Shift+O',
          click: async () => {
            const result = await dialog.showOpenDialog(mainWindow, {
              properties: ['openDirectory']
            });
            if (!result.canceled) {
              mainWindow.webContents.send('menu-open-folder', result.filePaths[0]);
            }
          }
        },
        { type: 'separator' },
        {
          label: 'Save',
          accelerator: 'CmdOrCtrl+S',
          click: () => mainWindow.webContents.send('menu-save')
        },
        {
          label: 'Save As',
          accelerator: 'CmdOrCtrl+Shift+S',
          click: () => mainWindow.webContents.send('menu-save-as')
        },
        { type: 'separator' },
        {
          label: 'Exit',
          accelerator: 'CmdOrCtrl+Q',
          click: () => app.quit()
        }
      ]
    },
    {
      label: 'Edit',
      submenu: [
        { role: 'undo' },
        { role: 'redo' },
        { type: 'separator' },
        { role: 'cut' },
        { role: 'copy' },
        { role: 'paste' },
        { role: 'selectAll' },
        { type: 'separator' },
        {
          label: 'Find',
          accelerator: 'CmdOrCtrl+F',
          click: () => mainWindow.webContents.send('menu-find')
        },
        {
          label: 'Replace',
          accelerator: 'CmdOrCtrl+H',
          click: () => mainWindow.webContents.send('menu-replace')
        }
      ]
    },
    {
      label: 'View',
      submenu: [
        {
          label: 'Toggle Sidebar',
          accelerator: 'CmdOrCtrl+B',
          click: () => mainWindow.webContents.send('menu-toggle-sidebar')
        },
        {
          label: 'Toggle Terminal',
          accelerator: 'CmdOrCtrl+`',
          click: () => mainWindow.webContents.send('menu-toggle-terminal')
        },
        {
          label: 'Toggle Preview',
          accelerator: 'CmdOrCtrl+Shift+V',
          click: () => mainWindow.webContents.send('menu-toggle-preview')
        },
        { type: 'separator' },
        { role: 'reload' },
        { role: 'forceReload' },
        { role: 'toggleDevTools' },
        { type: 'separator' },
        { role: 'resetZoom' },
        { role: 'zoomIn' },
        { role: 'zoomOut' },
        { type: 'separator' },
        { role: 'togglefullscreen' }
      ]
    },
    {
      label: 'Terminal',
      submenu: [
        {
          label: 'New Terminal',
          accelerator: 'CmdOrCtrl+Shift+`',
          click: () => mainWindow.webContents.send('menu-new-terminal')
        },
        {
          label: 'Split Terminal',
          click: () => mainWindow.webContents.send('menu-split-terminal')
        },
        {
          label: 'Kill Terminal',
          click: () => mainWindow.webContents.send('menu-kill-terminal')
        }
      ]
    },
    {
      label: 'Extensions',
      submenu: [
        {
          label: 'Manage Extensions',
          accelerator: 'CmdOrCtrl+Shift+X',
          click: () => mainWindow.webContents.send('menu-extensions')
        },
        {
          label: 'Install Extension',
          click: () => mainWindow.webContents.send('menu-install-extension')
        }
      ]
    },
    {
      label: 'Help',
      submenu: [
        {
          label: 'Documentation',
          click: () => mainWindow.webContents.send('menu-documentation')
        },
        {
          label: 'About SafeCode IDE',
          click: () => mainWindow.webContents.send('menu-about')
        }
      ]
    }
  ];

  const menu = Menu.buildFromTemplate(template);
  Menu.setApplicationMenu(menu);
}

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 800,
    minHeight: 600,
    backgroundColor: '#000000',
    icon: path.join(__dirname, 'assets/img/logos (6).png'),
    webPreferences: {
      nodeIntegration: false,
      contextIsolation: true,
      preload: path.join(__dirname, 'electron-preload.js')
    },
    titleBarStyle: 'hidden',
    titleBarOverlay: {
      color: '#000000',
      symbolColor: '#ffffff',
      height: 40
    },
    autoHideMenuBar: true,
    frame: false
  });

  mainWindow.setMenu(null); // Completely remove native menu bar
  mainWindow.setMenuBarVisibility(false);

  // Carregar a aplicação
  mainWindow.loadFile('index.html');
  mainWindow.webContents.openDevTools();

  createMenu();

  mainWindow.on('closed', () => {
    mainWindow = null;
    if (fileWatcher) {
      fileWatcher.close();
    }
  });
}

// IPC Handlers - Sistema de Arquivos
ipcMain.handle('fs:readFile', async (event, filePath) => {
  try {
    const content = await fs.readFile(filePath, 'utf-8');
    return { success: true, content };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('fs:writeFile', async (event, filePath, content) => {
  try {
    await fs.writeFile(filePath, content, 'utf-8');
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('fs:readDir', async (event, dirPath) => {
  try {
    const entries = await fs.readdir(dirPath, { withFileTypes: true });
    const items = entries.map(entry => ({
      name: entry.name,
      path: path.join(dirPath, entry.name),
      isDirectory: entry.isDirectory(),
      isFile: entry.isFile()
    }));
    return { success: true, items };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('fs:createFile', async (event, filePath) => {
  try {
    await fs.writeFile(filePath, '', 'utf-8');
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('fs:createDir', async (event, dirPath) => {
  try {
    await fs.mkdir(dirPath, { recursive: true });
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('fs:delete', async (event, targetPath) => {
  try {
    const stats = await fs.stat(targetPath);
    if (stats.isDirectory()) {
      await fs.rmdir(targetPath, { recursive: true });
    } else {
      await fs.unlink(targetPath);
    }
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('fs:rename', async (event, oldPath, newPath) => {
  try {
    await fs.rename(oldPath, newPath);
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

// Dialog Handlers
ipcMain.handle('dialog:openFile', async () => {
  const result = await dialog.showOpenDialog(mainWindow, {
    properties: ['openFile'],
    filters: [
      { name: 'All Files', extensions: ['*'] },
      { name: 'JavaScript', extensions: ['js', 'jsx', 'ts', 'tsx'] },
      { name: 'HTML', extensions: ['html', 'htm'] },
      { name: 'CSS', extensions: ['css', 'scss', 'sass'] },
      { name: 'JSON', extensions: ['json'] }
    ]
  });
  if (!result.canceled) {
    return result.filePaths[0];
  }
  return null;
});

ipcMain.handle('dialog:openDirectory', async () => {
  const result = await dialog.showOpenDialog(mainWindow, {
    properties: ['openDirectory']
  });
  if (!result.canceled) {
    return result.filePaths[0];
  }
  return null;
});

ipcMain.handle('dialog:saveFile', async (event, defaultPath) => {
  const result = await dialog.showSaveDialog(mainWindow, {
    defaultPath: defaultPath,
    filters: [
      { name: 'All Files', extensions: ['*'] }
    ]
  });
  if (!result.canceled) {
    return result.filePath;
  }
  return null;
});

// File Watcher
ipcMain.handle('fs:watchDir', async (event, dirPath) => {
  try {
    if (fileWatcher) {
      fileWatcher.close();
    }

    fileWatcher = chokidar.watch(dirPath, {
      ignored: /(^|[\/\\])\../, // ignore dotfiles
      persistent: true,
      ignoreInitial: true
    });

    fileWatcher
      .on('add', path => mainWindow.webContents.send('fs:file-added', path))
      .on('change', path => mainWindow.webContents.send('fs:file-changed', path))
      .on('unlink', path => mainWindow.webContents.send('fs:file-deleted', path))
      .on('addDir', path => mainWindow.webContents.send('fs:dir-added', path))
      .on('unlinkDir', path => mainWindow.webContents.send('fs:dir-deleted', path));

    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

let pty = null;
try {
  pty = require('node-pty');
} catch (e) {
  console.error('Failed to load node-pty. Terminal functionality will be limited.', e);
}

const os = require('os');

const shell = process.platform === 'win32' ? 'powershell.exe' : 'bash';
const ptyProcesses = new Map();

let previewWindow = null;

// IPC Handlers - PTY Terminal
ipcMain.handle('terminal:create', (event, terminalId) => {
  if (!pty) {
    return { success: false, error: 'Terminal backend (node-pty) not available' };
  }

  try {
    const ptyProcess = pty.spawn(shell, [], {
      name: 'xterm-color',
      cols: 80,
      rows: 30,
      cwd: process.cwd(),
      env: process.env
    });

    ptyProcess.onData((data) => {
      mainWindow.webContents.send(`terminal:data-${terminalId}`, data);
    });

    ptyProcesses.set(terminalId, ptyProcess);
    return { success: true };
  } catch (e) {
    console.error('Failed to spawn PTY process:', e);
    return { success: false, error: e.message };
  }
});

ipcMain.handle('terminal:write', (event, terminalId, data) => {
  const ptyProcess = ptyProcesses.get(terminalId);
  if (ptyProcess) {
    ptyProcess.write(data);
    return { success: true };
  }
  return { success: false, error: 'Terminal not found' };
});

ipcMain.handle('terminal:resize', (event, terminalId, cols, rows) => {
  const ptyProcess = ptyProcesses.get(terminalId);
  if (ptyProcess) {
    ptyProcess.resize(cols, rows);
    return { success: true };
  }
  return { success: false, error: 'Terminal not found' };
});

ipcMain.handle('terminal:kill', (event, terminalId) => {
  const ptyProcess = ptyProcesses.get(terminalId);
  if (ptyProcess) {
    ptyProcess.kill();
    ptyProcesses.delete(terminalId);
    return { success: true };
  }
  return { success: false, error: 'Terminal not found' };
});

// Preview Window logic
ipcMain.handle('preview:open', (event, url) => {
  if (previewWindow) {
    previewWindow.loadURL(url);
    previewWindow.focus();
  } else {
    previewWindow = new BrowserWindow({
      width: 1000,
      height: 800,
      title: 'SafeCode Preview',
      autoHideMenuBar: true,
      webPreferences: {
        nodeIntegration: false,
        contextIsolation: true
      }
    });

    previewWindow.loadURL(url);
    previewWindow.on('closed', () => {
      previewWindow = null;
    });
  }
  return { success: true };
});

ipcMain.handle('preview:refresh', () => {
  if (previewWindow) {
    previewWindow.webContents.reload();
    return { success: true };
  }
  return { success: false };
});

ipcMain.handle('git:init', async (event, cwd) => {
  try {
    await execPromise('git init', { cwd });
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

// Git Handlers
ipcMain.handle('git:status', async (event, cwd) => {
  try {
    const { stdout } = await execPromise('git status --porcelain', { cwd });
    return { success: true, status: stdout };
  } catch (error) {
    if (error.message.includes('not a git repository')) {
      return { success: true, status: null, isRepo: false };
    }
    return { success: false, error: error.message };
  }
});

ipcMain.handle('git:stage', async (event, cwd, filePath) => {
  try {
    await execPromise(`git add "${filePath}"`, { cwd });
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('git:unstage', async (event, cwd, filePath) => {
  try {
    await execPromise(`git reset HEAD "${filePath}"`, { cwd });
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('git:commit', async (event, cwd, message) => {
  try {
    await execPromise(`git commit -m "${message}"`, { cwd });
    return { success: true };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('git:diff', async (event, cwd, filePath) => {
  try {
    // Relative path for git diff
    const relativePath = path.relative(cwd, filePath);
    const { stdout } = await execPromise(`git diff -U0 "${relativePath}"`, { cwd });
    return { success: true, diff: stdout };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

// Extensions Handlers
ipcMain.handle('extensions:list', async () => {
  try {
    const extensionsPath = path.join(__dirname, 'extensions');
    // Create directory if not exists
    try {
      await fs.access(extensionsPath);
    } catch {
      await fs.mkdir(extensionsPath);
    }

    const entries = await fs.readdir(extensionsPath, { withFileTypes: true });
    const extensions = [];

    for (const entry of entries) {
      if (entry.isDirectory()) {
        const manifestPath = path.join(extensionsPath, entry.name, 'package.json');
        try {
          const manifestContent = await fs.readFile(manifestPath, 'utf-8');
          const manifest = JSON.parse(manifestContent);
          extensions.push({
            id: entry.name,
            path: path.join(extensionsPath, entry.name),
            manifest
          });
        } catch (e) {
          console.warn(`Failed to load extension manifest for ${entry.name}:`, e.message);
        }
      }
    }
    return { success: true, extensions };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

ipcMain.handle('extensions:readFile', async (event, extensionId, fileName) => {
  try {
    const filePath = path.join(__dirname, 'extensions', extensionId, fileName);
    const content = await fs.readFile(filePath, 'utf-8');
    return { success: true, content };
  } catch (error) {
    return { success: false, error: error.message };
  }
});

// App lifecycle
app.whenReady().then(createWindow);

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

app.on('activate', () => {
  if (BrowserWindow.getAllWindows().length === 0) {
    createWindow();
  }
});
