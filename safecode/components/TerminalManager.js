/**
 * TerminalManager - Manages terminal instances using xterm.js
 */

class TerminalManager {
    constructor(ide) {
        this.ide = ide;
        this.terminals = new Map(); // id -> { terminal, element }
        this.activeTerminal = null;
        this.terminalCounter = 0;
    }

    createTerminal() {
        const terminalId = `terminal-${this.terminalCounter++}`;
        const panelContent = document.getElementById('panelContent');
        if (!panelContent) return;

        // Ensure panelContent has flex layout for splitting
        panelContent.style.display = 'flex';
        panelContent.style.gap = '1px';
        panelContent.style.height = '100%';
        panelContent.style.background = 'rgba(255,255,255,0.05)';

        // Create terminal container
        const terminalContainer = document.createElement('div');
        terminalContainer.className = 'terminal-instance';
        terminalContainer.id = terminalId;
        terminalContainer.style.flex = '1';
        terminalContainer.style.minWidth = '0';
        terminalContainer.style.height = '100%';
        terminalContainer.style.background = '#000000';
        terminalContainer.style.position = 'relative';

        // Create xterm instance
        const terminal = new Terminal({
            cursorBlink: true,
            fontSize: 13,
            fontFamily: 'JetBrains Mono, monospace',
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

        // Add addons
        let fitAddon, webLinksAddon;

        try {
            if (typeof FitAddon !== 'undefined') {
                fitAddon = typeof FitAddon.FitAddon === 'function' ? new FitAddon.FitAddon() : new FitAddon();
            }
            if (typeof WebLinksAddon !== 'undefined') {
                webLinksAddon = typeof WebLinksAddon.WebLinksAddon === 'function' ? new WebLinksAddon.WebLinksAddon() : new WebLinksAddon();
            }
        } catch (e) {
            console.warn('Failed to initialize terminal addons:', e);
        }

        if (fitAddon) terminal.loadAddon(fitAddon);
        if (webLinksAddon) terminal.loadAddon(webLinksAddon);

        // Open terminal
        terminal.open(terminalContainer);

        // Ensure bottom panel is visible
        const bottomPanelEl = document.getElementById('bottomPanel');
        if (bottomPanelEl) bottomPanelEl.style.display = 'flex';

        this.ide.switchPanel('terminal');

        // Use real PTY if in Electron
        if (this.ide.isElectron && window.electronAPI && window.electronAPI.terminal) {
            this.setupPTYTerminal(terminal, terminalId);
        } else {
            this.setupWebTerminal(terminal);
            terminal.writeln('\x1b[1;35mSafeCode IDE Terminal (Web Mode)\x1b[0m');
            terminal.writeln('Type commands here...\n');
        }

        // Add to panel
        panelContent.appendChild(terminalContainer);

        // Force layout and fit
        setTimeout(() => {
            fitAddon.fit();
        }, 100);

        // Store terminal
        this.terminals.set(terminalId, {
            terminal,
            element: terminalContainer,
            fitAddon
        });

        this.setActiveTerminal(terminalId);

        // Show terminal panel
        const panel = document.getElementById('bottomPanel');
        if (panel) {
            panel.style.display = 'flex';
        }

        // Handle focus
        terminalContainer.addEventListener('mousedown', () => {
            this.setActiveTerminal(terminalId);
        });

        return terminalId;
    }

    setActiveTerminal(terminalId) {
        this.activeTerminal = terminalId;
        this.terminals.forEach((data, id) => {
            if (id === terminalId) {
                data.element.style.borderTop = '1px solid #ffffff';
                data.terminal.focus();
            } else {
                data.element.style.borderTop = '1px solid transparent';
            }
        });
    }

    async setupPTYTerminal(terminal, terminalId) {
        const result = await window.electronAPI.terminal.create(terminalId);

        if (!result.success) {
            console.warn('Backend terminal creation failed, falling back to Web Mode:', result.error);
            terminal.writeln(`\r\n\x1b[1;33mWarning: Native terminal unavailable (${result.error}).\x1b[0m`);
            terminal.writeln('\x1b[1;35mFalling back to SafeCode Web Mode.\x1b[0m\r\n');
            this.setupWebTerminal(terminal);
            return;
        }

        terminal.onData(data => {
            window.electronAPI.terminal.write(terminalId, data);
        });

        window.electronAPI.terminal.onData(terminalId, data => {
            terminal.write(data);
        });

        // Initial resize
        const dims = terminal.element.getBoundingClientRect();
        // Rough estimate of cols/rows
        const cols = Math.floor(dims.width / 8);
        const rows = Math.floor(dims.height / 18);
        window.electronAPI.terminal.resize(terminalId, cols || 80, rows || 30);
    }

    setupWebTerminal(terminal) {
        let currentLine = '';
        const prompt = '\x1b[1;32m$\x1b[0m ';

        terminal.write(prompt);

        terminal.onData(data => {
            switch (data) {
                case '\r': // Enter
                    terminal.write('\r\n');
                    this.executeCommand(terminal, currentLine);
                    currentLine = '';
                    terminal.write(prompt);
                    break;
                case '\u007F': // Backspace
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

        // Simple command simulation
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
                    terminal.writeln('Type "help" for available commands');
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

    clearActiveTerminal() {
        if (this.activeTerminal) {
            const terminalData = this.terminals.get(this.activeTerminal);
            if (terminalData) {
                terminalData.terminal.clear();
            }
        }
    }

    async killTerminal(terminalId) {
        const terminalData = this.terminals.get(terminalId);
        if (terminalData) {
            if (this.ide.isElectron && window.electronAPI && window.electronAPI.terminal) {
                await window.electronAPI.terminal.kill(terminalId);
            }
            terminalData.terminal.dispose();
            terminalData.element.remove();
            this.terminals.delete(terminalId);

            // If no terminals left, hide panel
            if (this.terminals.size === 0) {
                const panel = document.getElementById('bottomPanel');
                if (panel) {
                    panel.style.display = 'none';
                }
                this.activeTerminal = null;
            } else {
                // Activate another terminal
                this.setActiveTerminal(Array.from(this.terminals.keys())[0]);
            }
        }
    }

    resizeTerminals() {
        this.terminals.forEach(({ terminal, fitAddon, element }, id) => {
            fitAddon.fit();
            if (this.ide.isElectron && window.electronAPI && window.electronAPI.terminal) {
                const dims = terminal; // terminal object contains some info, but fitAddon handles rows/cols
                // We can't easily get core/cols from fitAddon directly without access to internal, but terminal.cols/rows exist
                window.electronAPI.terminal.resize(id, terminal.cols, terminal.rows);
            }
        });
    }
}

window.TerminalManager = TerminalManager;
