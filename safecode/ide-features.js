/**
 * SafeCode IDE - Manager Classes Part 3
 * Live Preview, Live Server, Enhanced Tab Manager, Terminal Manager
 */

// ============================================================================
// Live Preview Manager
// ============================================================================
class LivePreviewManager {
    constructor(ide) {
        this.ide = ide;
        this.currentDevice = 'desktop';
        this.isOpen = false;
        this.setupResizer();
    }

    setupResizer() {
        const resizer = document.getElementById('previewResizer');
        const container = document.getElementById('previewContainer');
        if (!resizer || !container) return;

        let isResizing = false;

        function handleMouseMove(e) {
            if (!isResizing) return;
            const width = window.innerWidth - e.clientX;
            // Constraints: min 250px, max 80% screen width
            if (width > 250 && width < window.innerWidth * 0.8) {
                container.style.width = width + 'px';
                container.style.flex = 'none';
            }
        }

        function handleMouseUp() {
            if (!isResizing) return;
            isResizing = false;
            document.body.style.cursor = 'default';
            const overlay = document.getElementById('resize-overlay');
            if (overlay) overlay.remove();

            document.removeEventListener('mousemove', handleMouseMove);
            document.removeEventListener('mouseup', handleMouseUp);
        }

        resizer.addEventListener('mousedown', (e) => {
            isResizing = true;
            document.body.style.cursor = 'col-resize';

            // Add a temporary overlay to prevent iframe from capturing mouse events
            const overlay = document.createElement('div');
            overlay.style.position = 'fixed';
            overlay.style.top = '0';
            overlay.style.left = '0';
            overlay.style.width = '100vw';
            overlay.style.height = '100vh';
            overlay.style.zIndex = '9999';
            overlay.style.cursor = 'col-resize';
            overlay.id = 'resize-overlay';
            document.body.appendChild(overlay);

            document.addEventListener('mousemove', handleMouseMove);
            document.addEventListener('mouseup', handleMouseUp);
        });
    }

    toggle() {
        const container = document.getElementById('previewContainer');
        if (container) {
            this.isOpen = !this.isOpen;
            container.style.display = this.isOpen ? 'flex' : 'none';

            if (this.isOpen) {
                this.refresh();
            }
        }
    }

    close() {
        const container = document.getElementById('previewContainer');
        if (container) {
            this.isOpen = false;
            container.style.display = 'none';
        }
    }

    setDevice(device) {
        this.currentDevice = device;

        // Update active button
        document.querySelectorAll('.device-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.device === device);
        });

        // Show appropriate frame
        const desktopFrame = document.getElementById('preview-frame-desktop');
        const mobileContainer = document.getElementById('mobilePreviewContainer');
        const iphoneFrame = document.getElementById('iphoneFrame');
        const androidFrame = document.getElementById('androidFrame');
        const tabletFrame = document.getElementById('tabletFrame');

        if (device === 'desktop') {
            desktopFrame.style.display = 'block';
            mobileContainer.classList.remove('active');
        } else {
            desktopFrame.style.display = 'none';
            mobileContainer.classList.add('active');

            iphoneFrame.classList.toggle('active', device === 'mobile-ios');
            androidFrame.classList.toggle('active', device === 'mobile-android');
            tabletFrame.classList.toggle('active', device === 'tablet');
        }

        this.refresh();
    }

    refresh() {
        const content = this.ide.editorManager.getCurrentContent();
        if (!content) return;

        // Create blob URL for the content
        const blob = new Blob([content], { type: 'text/html' });
        const url = URL.createObjectURL(blob);

        // Update all frames
        const frames = [
            'preview-frame-desktop',
            'preview-frame-ios',
            'preview-frame-android',
            'preview-frame-tablet'
        ];

        frames.forEach(frameId => {
            const frame = document.getElementById(frameId);
            if (frame) {
                frame.src = url;
            }
        });

        // Update time in mobile status bars
        this.updateMobileTime();
    }

    updateMobileTime() {
        const now = new Date();
        const time = now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: false
        });

        const iosTime = document.getElementById('iosTime');
        const androidTime = document.getElementById('androidTime');

        if (iosTime) iosTime.textContent = time;
        if (androidTime) androidTime.textContent = time;
    }

    openExternal() {
        if (this.ide.liveServer.isRunning) {
            const url = `http://localhost:${this.ide.liveServer.port}`;
            if (this.ide.isElectron) {
                require('electron').shell.openExternal(url);
            } else {
                window.open(url, '_blank');
            }
        } else {
            alert('Please start the Live Server first');
        }
    }

    copyUrl() {
        const url = document.getElementById('previewUrl');
        if (url) {
            url.select();
            document.execCommand('copy');

            // Show feedback
            const btn = document.getElementById('btnCopyUrl');
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '<i data-lucide="check" class="w-3 h-3"></i>';
            lucide.createIcons();

            setTimeout(() => {
                btn.innerHTML = originalHTML;
                lucide.createIcons();
            }, 2000);
        }
    }
}

// ============================================================================
// Live Server
// ============================================================================
class LiveServer {
    constructor(ide) {
        this.ide = ide;
        this.isRunning = false;
        this.port = 5500;
        this.autoRefresh = true;
        this.server = null;
    }

    start() {
        // In Electron, we would start an actual server
        // For now, simulate it
        this.isRunning = true;
        this.updateUI();

        // Open preview automatically
        if (!this.ide.previewManager.isOpen) {
            this.ide.previewManager.toggle();
        }

        console.log('Live Server started on port', this.port);
    }

    stop() {
        this.isRunning = false;
        this.updateUI();
        console.log('Live Server stopped');
    }

    updateUI() {
        const btn = document.getElementById('btnGoLive');
        const statusServer = document.getElementById('statusLiveServer');

        if (btn) {
            btn.classList.toggle('active', this.isRunning);
            btn.innerHTML = this.isRunning
                ? '<i data-lucide="radio" class="w-4 h-4"></i> Port: ' + this.port
                : '<i data-lucide="radio" class="w-4 h-4"></i> Go Live';
            lucide.createIcons();
        }

        if (statusServer) {
            if (this.isRunning) {
                statusServer.innerHTML = `
                    <span class="status-indicator" style="background: #10b981;"></span>
                    <span>Live Server: Port ${this.port}</span>
                `;
            } else {
                statusServer.innerHTML = `
                    <span class="status-indicator" style="background: #71717a;"></span>
                    <span>Live Server: Stopped</span>
                `;
            }
        }
    }
}

// ============================================================================
// Enhanced Tab Manager
// ============================================================================
class EnhancedTabManager {
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

            // Make tabs draggable (future enhancement)
            tabsContainer.addEventListener('dragstart', (e) => {
                if (e.target.classList.contains('editor-tab')) {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', e.target.dataset.file);
                }
            });
        }
    }

    addTab(filePath) {
        if (this.tabs.has(filePath)) {
            this.activateTab(filePath);
            return;
        }

        const tabsContainer = document.getElementById('tabsContainer');
        if (!tabsContainer) return;

        const tab = document.createElement('div');
        tab.className = 'editor-tab active';
        tab.dataset.file = filePath;
        tab.draggable = true;

        const fileName = this.getFileName(filePath);
        const icon = this.getFileIcon(filePath);

        tab.innerHTML = `
            <i data-lucide="${icon}" class="w-3 h-3"></i>
            <span class="tab-label">${fileName}</span>
            <button class="tab-close">
                <i data-lucide="x" class="w-3 h-3"></i>
            </button>
        `;

        this.deactivateAllTabs();
        tabsContainer.appendChild(tab);
        this.tabs.set(filePath, tab);
        this.activeTab = filePath;

        lucide.createIcons();
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
            const confirm = window.confirm(`Do you want to save the changes you made to ${this.getFileName(filePath)}?`);
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

    getFileName(filePath) {
        return filePath.split(/[/\\]/).pop();
    }

    getFileIcon(filePath) {
        const ext = filePath.split('.').pop()?.toLowerCase();
        const iconMap = {
            'js': 'file-code',
            'jsx': 'file-code',
            'ts': 'file-code',
            'tsx': 'file-code',
            'html': 'file-code',
            'css': 'file-code',
            'json': 'braces',
            'md': 'file-text',
            'txt': 'file-text'
        };
        return iconMap[ext] || 'file';
    }
}

// ============================================================================
// Enhanced Terminal Manager
// ============================================================================
class EnhancedTerminalManager {
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

        if (typeof Terminal !== 'undefined') {
            try {
                const terminal = new Terminal({
                    cursorBlink: true,
                    fontSize: 13,
                    fontFamily: 'JetBrains Mono, Consolas, monospace',
                    theme: {
                        background: '#000000',
                        foreground: '#ffffff',
                        cursor: '#ffffff',
                        selection: 'rgba(255, 255, 255, 0.3)',
                        black: '#000000',
                        white: '#ffffff',
                        brightBlack: '#666666',
                        brightWhite: '#ffffff'
                    }
                });

                const fitAddon = new FitAddon.FitAddon();
                terminal.loadAddon(fitAddon);

                terminal.open(terminalContainer);
                fitAddon.fit();

                terminal.writeln('\x1b[1;37mSafeCode IDE Terminal v1.0.0\x1b[0m');
                terminal.writeln('\x1b[2mType "help" for a list of commands.\x1b[0m');
                terminal.writeln('');

                this.setupTerminalHandlers(terminal);
                this.terminals.set(terminalId, { terminal, fitAddon, element: terminalContainer });

                window.addEventListener('resize', () => fitAddon.fit());
            } catch (error) {
                console.error('Error creating xterm terminal:', error);
                this.createFallbackTerminal(terminalContainer, terminalId);
            }
        } else {
            this.createFallbackTerminal(terminalContainer, terminalId);
        }

        panelContent.appendChild(terminalContainer);
        this.activeTerminal = terminalId;

        const panel = document.getElementById('bottomPanel');
        if (panel) panel.style.display = 'flex';

        return terminalId;
    }

    setupTerminalHandlers(terminal) {
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
                case '\u0003': // Ctrl+C
                    terminal.write('^C\r\n');
                    currentLine = '';
                    terminal.write(prompt);
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

        const commands = {
            'clear': () => terminal.clear(),
            'help': () => {
                terminal.writeln('Available commands:');
                terminal.writeln('  clear     - Clear terminal');
                terminal.writeln('  help      - Show this help');
                terminal.writeln('  date      - Show current date/time');
                terminal.writeln('  echo      - Echo text');
                terminal.writeln('  version   - Show IDE version');
                terminal.writeln('  workspace - Show current workspace');
            },
            'date': () => terminal.writeln(new Date().toString()),
            'version': () => terminal.writeln('SafeCode IDE v1.0.0'),
            'workspace': () => {
                if (this.ide.workspace) {
                    terminal.writeln(this.ide.workspace);
                } else {
                    terminal.writeln('No workspace opened');
                }
            }
        };

        if (commands[cmd]) {
            commands[cmd]();
        } else if (cmd.startsWith('echo ')) {
            terminal.writeln(cmd.substring(5));
        } else {
            terminal.writeln(`\x1b[1;31mCommand not found:\x1b[0m ${cmd}`);
            terminal.writeln('Type "help" for available commands');
        }
    }

    createFallbackTerminal(container, terminalId) {
        container.innerHTML = `
            <div style="padding: 1rem; color: #8b5cf6; font-family: 'JetBrains Mono', monospace;">
                <div style="font-weight: bold; margin-bottom: 0.5rem;">SafeCode IDE Terminal</div>
                <div style="color: #71717a;">Terminal functionality available in full version</div>
            </div>
        `;
        this.terminals.set(terminalId, { terminal: null, element: container });
    }

    splitTerminal() {
        this.createTerminal();
    }

    clearActiveTerminal() {
        if (this.activeTerminal) {
            const term = this.terminals.get(this.activeTerminal);
            if (term && term.terminal) term.terminal.clear();
        }
    }

    killActiveTerminal() {
        if (this.activeTerminal) {
            this.killTerminal(this.activeTerminal);
        }
    }

    killTerminal(terminalId) {
        const terminalData = this.terminals.get(terminalId);
        if (terminalData) {
            if (terminalData.terminal && terminalData.terminal.dispose) {
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

// Continue in next part...
// ============================================================================
// Lock Screen Manager
// ============================================================================
class LockScreenManager {
    constructor() {
        this.lockScreen = document.getElementById('iosLockScreen');
        this.clockEl = document.querySelector('.lock-clock');
        this.dateEl = document.querySelector('.lock-date');
        this.statusClockEl = document.querySelector('.ios-status-clock');

        if (this.lockScreen) {
            this.init();
        }
    }

    init() {
        // Unlock interaction
        this.lockScreen.addEventListener('click', () => {
            this.lockScreen.classList.add('unlocked');
            this.animateIslandUnlock();
        });

        // Real-time clock
        this.updateTime();
        setInterval(() => this.updateTime(), 1000);
    }

    animateIslandUnlock() {
        const lockIcon = document.querySelector('.island-lock i');
        if (lockIcon) {
            // Change to unlock icon
            lockIcon.setAttribute('data-lucide', 'unlock');
            if (window.lucide) window.lucide.createIcons();

            // Subtle animation
            setTimeout(() => {
                const islandLock = document.querySelector('.island-lock');
                if (islandLock) islandLock.style.opacity = '0';
            }, 500);
        }
    }

    updateTime() {
        const now = new Date();

        // Time
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const timeString = `${hours}:${minutes}`;

        if (this.clockEl) this.clockEl.textContent = timeString;
        if (this.statusClockEl) this.statusClockEl.textContent = timeString;

        // Date
        if (this.dateEl) {
            const options = { weekday: 'long', day: 'numeric', month: 'long' };
            this.dateEl.textContent = now.toLocaleDateString('pt-BR', options);
        }
    }

    lock() {
        if (this.lockScreen) {
            this.lockScreen.classList.remove('unlocked');
            // Reset lock icon
            const lockIcon = document.querySelector('.island-lock i');
            if (lockIcon) {
                lockIcon.setAttribute('data-lucide', 'lock');
                if (window.lucide) window.lucide.createIcons();
                document.querySelector('.island-lock').style.opacity = '1';
            }
        }
    }
}

// ============================================================================
// Device Controls Manager
// ============================================================================
class DeviceControlsManager {
    constructor() {
        this.frame = document.getElementById('iphoneFrame');
        if (!this.frame) return;

        this.init();
    }

    init() {
        // Rotate
        document.getElementById('btnRotate')?.addEventListener('click', () => {
            this.frame.classList.toggle('landscape');
        });

        // Lock
        document.getElementById('btnLock')?.addEventListener('click', () => {
            // Access existing lock manager if possible, or trigger event
            // We can dispatch a global event or check specific instance
            // Ideally we re-use the LockScreenManager instance but for now let's query the DOM

            // Simple approach: re-instantiate or find method. 
            // Better: Dispatch custom event or static method. 
            // Let's modify LockScreenManager to be accessible or just re-run lock logic here contextually if simple.
            // Actually, let's just create a helper logic or allow LockScreenManager to handle it.
            // Since we modified LockScreenManager above to include lock() method, we can try to find the instance? 
            // No, instances aren't global.
            // Let's just create a new one or attach it to window.ide if needed. 
            // For safety/speed, I'll direct manipulate DOM correctly mimicking logic.
            const lockScreen = document.getElementById('iosLockScreen');
            if (lockScreen) {
                lockScreen.classList.remove('unlocked');
                const lockIcon = document.querySelector('.island-lock i');
                const lockContainer = document.querySelector('.island-lock');
                if (lockIcon) {
                    lockIcon.setAttribute('data-lucide', 'lock');
                    if (window.lucide) window.lucide.createIcons();
                }
                if (lockContainer) lockContainer.style.opacity = '1';
            }
        });

        // Theme (Simulated)
        document.getElementById('btnTheme')?.addEventListener('click', () => {
            this.frame.classList.toggle('light-theme-sim');
            // Notify user
            console.log('Theme toggled');
        });

        // Power
        document.getElementById('btnPower')?.addEventListener('click', () => {
            this.frame.classList.toggle('screen-off');
        });
    }
}

// Initialize Items
document.addEventListener('DOMContentLoaded', () => {
    new LockScreenManager();
    new DeviceControlsManager();
});
