/**
 * TerminalManager - Manages terminal instances using xterm.js
 * REFATORADO - Versão completa e funcional
 */

class TerminalManager {
    constructor(ide) {
        this.ide = ide;
        this.terminals = new Map(); // id -> { terminal, container, fitAddon, webLinksAddon }
        this.activeTerminal = null;
        this.terminalCounter = 0;
    }

    /**
     * Create a new terminal instance
     */
    createTerminal() {
        try {
            const terminalId = `terminal-${this.terminalCounter++}`;
            
            // Get panel elements
            const panelTerminal = document.getElementById('panel-terminal');
            if (!panelTerminal) {
                console.error('[TerminalManager] panel-terminal element not found');
                return null;
            }

            // Ensure bottom panel is open
            const bottomPanel = document.getElementById('bottomPanel');
            if (bottomPanel) {
                bottomPanel.classList.add('open');
            }

            // Switch to terminal panel
            if (this.ide && typeof this.ide.switchPanel === 'function') {
                this.ide.switchPanel('terminal');
            }

            // Create terminal container
            const container = document.createElement('div');
            container.className = 'terminal-instance';
            container.id = terminalId;
            container.style.cssText = 'width: 100%; height: 100%; background: #000000; position: relative; overflow: hidden;';

            // Add to DOM first (required by xterm.js)
            panelTerminal.appendChild(container);

            // Check if Terminal library is available
            if (typeof Terminal === 'undefined') {
                console.error('[TerminalManager] Terminal (xterm.js) is not loaded');
                container.innerHTML = '<div style="padding: 1rem; color: #ef4444;">Terminal library not loaded. Please reload the page.</div>';
                return null;
            }

            // Create xterm instance
            const terminal = new Terminal({
                cursorBlink: true,
                fontSize: 13,
                fontFamily: 'JetBrains Mono, Consolas, monospace',
                theme: {
                    background: '#000000',
                    foreground: '#e4e4e7',
                    cursor: '#ffffff',
                    cursorAccent: '#000000',
                    selection: 'rgba(255, 255, 255, 0.3)',
                    black: '#000000',
                    red: '#ef4444',
                    green: '#10b981',
                    yellow: '#f59e0b',
                    blue: '#3b82f6',
                    magenta: '#8b5cf6',
                    cyan: '#06b6d4',
                    white: '#e4e4e7',
                    brightBlack: '#52525b',
                    brightRed: '#f87171',
                    brightGreen: '#34d399',
                    brightYellow: '#fbbf24',
                    brightBlue: '#60a5fa',
                    brightMagenta: '#a78bfa',
                    brightCyan: '#22d3ee',
                    brightWhite: '#ffffff'
                }
            });

            // Initialize addons
            let fitAddon = null;
            let webLinksAddon = null;

            try {
                // FitAddon
                if (typeof FitAddon !== 'undefined') {
                    if (typeof FitAddon.FitAddon === 'function') {
                        fitAddon = new FitAddon.FitAddon();
                    } else if (typeof FitAddon === 'function') {
                        fitAddon = new FitAddon();
                    }
                    if (fitAddon) {
                        terminal.loadAddon(fitAddon);
                    }
                }
                
                // WebLinksAddon
                if (typeof WebLinksAddon !== 'undefined') {
                    if (typeof WebLinksAddon.WebLinksAddon === 'function') {
                        webLinksAddon = new WebLinksAddon.WebLinksAddon();
                    } else if (typeof WebLinksAddon === 'function') {
                        webLinksAddon = new WebLinksAddon();
                    }
                    if (webLinksAddon) {
                        terminal.loadAddon(webLinksAddon);
                    }
                }
            } catch (e) {
                console.warn('[TerminalManager] Failed to initialize addons:', e);
            }

            // Open terminal
            terminal.open(container);

            // Store terminal data
            const terminalData = {
                terminal: terminal,
                container: container,
                fitAddon: fitAddon,
                webLinksAddon: webLinksAddon
            };
            this.terminals.set(terminalId, terminalData);

            // Set as active
            this.setActiveTerminal(terminalId);

            // Setup terminal based on environment
            if (this.ide.isElectron && window.electronAPI && window.electronAPI.terminal) {
                this.setupPTYTerminal(terminalId, terminalData);
            } else {
                this.setupWebTerminal(terminal, terminalData);
                terminal.writeln('\x1b[1;35mSafeCode IDE Terminal (Web Mode)\x1b[0m');
                terminal.writeln('Type "help" for available commands.\n');
            }

            // Fit terminal after a short delay
            setTimeout(() => {
                this.fitTerminal(terminalId);
            }, 150);

            // Focus terminal
            terminal.focus();

            return terminalId;
        } catch (error) {
            console.error('[TerminalManager] Error creating terminal:', error);
            return null;
        }
    }

    /**
     * Setup PTY terminal (Electron mode)
     */
    async setupPTYTerminal(terminalId, terminalData) {
        try {
            const { terminal } = terminalData;

            const result = await window.electronAPI.terminal.create(terminalId);
            if (!result.success) {
                console.warn('[TerminalManager] PTY creation failed, using web mode:', result.error);
                this.setupWebTerminal(terminal, terminalData);
                terminal.writeln('\r\n\x1b[1;33mWarning: Native terminal unavailable.\x1b[0m');
                terminal.writeln('\x1b[1;35mUsing Web Mode.\x1b[0m\r\n');
                return;
            }

            // Setup input
            terminal.onData(data => {
                if (window.electronAPI && window.electronAPI.terminal) {
                    window.electronAPI.terminal.write(terminalId, data);
                }
            });

            // Setup output
            if (window.electronAPI && window.electronAPI.terminal && window.electronAPI.terminal.onData) {
                window.electronAPI.terminal.onData(terminalId, (data) => {
                    terminal.write(data);
                });
            }

            // Initial resize
            setTimeout(() => {
                this.fitTerminal(terminalId);
                if (terminal.cols && terminal.rows && window.electronAPI && window.electronAPI.terminal) {
                    window.electronAPI.terminal.resize(terminalId, terminal.cols, terminal.rows);
                }
            }, 200);
        } catch (error) {
            console.error('[TerminalManager] Error setting up PTY terminal:', error);
            this.setupWebTerminal(terminalData.terminal, terminalData);
        }
    }

    /**
     * Setup web terminal (fallback mode)
     */
    setupWebTerminal(terminal, terminalData) {
        let currentLine = '';
        const prompt = '\x1b[1;32m$\x1b[0m ';

        terminal.write(prompt);

        terminal.onData(data => {
            const charCode = data.charCodeAt(0);

            if (data === '\r' || data === '\n') {
                terminal.write('\r\n');
                this.executeCommand(terminal, currentLine.trim());
                currentLine = '';
                terminal.write(prompt);
            } else if (data === '\u007F' || data === '\b') {
                if (currentLine.length > 0) {
                    currentLine = currentLine.slice(0, -1);
                    terminal.write('\b \b');
                }
            } else if (charCode >= 32) {
                currentLine += data;
                terminal.write(data);
            }
        });
    }

    /**
     * Execute command in web terminal
     */
    executeCommand(terminal, command) {
        if (!command) return;

        const cmd = command.toLowerCase().trim();

        switch (cmd) {
            case 'clear':
                terminal.clear();
                break;
            case 'help':
                terminal.writeln('\x1b[1;36mAvailable commands:\x1b[0m');
                terminal.writeln('  clear  - Clear terminal screen');
                terminal.writeln('  help   - Show this help message');
                terminal.writeln('  date   - Show current date and time');
                terminal.writeln('  echo   - Echo text back');
                break;
            case 'date':
                terminal.writeln(new Date().toString());
                break;
            default:
                if (command.startsWith('echo ')) {
                    terminal.writeln(command.substring(5));
                } else {
                    terminal.writeln(`\x1b[1;31mCommand not found: ${command}\x1b[0m`);
                    terminal.writeln('Type "help" for available commands.');
                }
        }
    }

    /**
     * Set active terminal
     */
    setActiveTerminal(terminalId) {
        if (!this.terminals.has(terminalId)) return;

        this.activeTerminal = terminalId;
        this.terminals.forEach((data, id) => {
            if (id === terminalId) {
                data.container.style.borderTop = '2px solid #ffffff';
                if (data.terminal && typeof data.terminal.focus === 'function') {
                    data.terminal.focus();
                }
            } else {
                data.container.style.borderTop = '2px solid transparent';
            }
        });
    }

    /**
     * Fit terminal to container
     */
    fitTerminal(terminalId) {
        const data = this.terminals.get(terminalId);
        if (!data || !data.fitAddon) return;

        try {
            if (typeof data.fitAddon.fit === 'function') {
                data.fitAddon.fit();
            }

            // Resize PTY if in Electron
            if (this.ide.isElectron && data.terminal && window.electronAPI && window.electronAPI.terminal) {
                if (data.terminal.cols && data.terminal.rows) {
                    window.electronAPI.terminal.resize(terminalId, data.terminal.cols, data.terminal.rows);
                }
            }
        } catch (e) {
            console.warn('[TerminalManager] Error fitting terminal:', e);
        }
    }

    /**
     * Resize all terminals
     */
    resizeTerminals() {
        this.terminals.forEach((data, id) => {
            this.fitTerminal(id);
        });
    }

    /**
     * Split terminal (creates new terminal)
     */
    splitTerminal() {
        return this.createTerminal();
    }

    /**
     * Kill active terminal
     */
    killActiveTerminal() {
        if (this.activeTerminal) {
            this.killTerminal(this.activeTerminal);
        }
    }

    /**
     * Clear active terminal
     */
    clearActiveTerminal() {
        if (this.activeTerminal) {
            const data = this.terminals.get(this.activeTerminal);
            if (data && data.terminal && typeof data.terminal.clear === 'function') {
                data.terminal.clear();
            }
        }
    }

    /**
     * Kill terminal
     */
    async killTerminal(terminalId) {
        const data = this.terminals.get(terminalId);
        if (!data) return;

        try {
            // Kill PTY if in Electron
            if (this.ide.isElectron && window.electronAPI && window.electronAPI.terminal) {
                await window.electronAPI.terminal.kill(terminalId);
            }

            // Dispose terminal
            if (data.terminal && typeof data.terminal.dispose === 'function') {
                data.terminal.dispose();
            }

            // Remove element
            if (data.container && data.container.parentNode) {
                data.container.parentNode.removeChild(data.container);
            }

            // Remove from map
            this.terminals.delete(terminalId);

            // Handle active terminal
            if (this.activeTerminal === terminalId) {
                if (this.terminals.size > 0) {
                    this.setActiveTerminal(Array.from(this.terminals.keys())[0]);
                } else {
                    this.activeTerminal = null;
                    const bottomPanel = document.getElementById('bottomPanel');
                    if (bottomPanel) {
                        bottomPanel.classList.remove('open');
                    }
                }
            }
        } catch (error) {
            console.error('[TerminalManager] Error killing terminal:', error);
        }
    }
}

window.TerminalManager = TerminalManager;
