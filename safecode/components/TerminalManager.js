/**
 * TerminalManager - Manages terminal instances using xterm.js
 */

import { Terminal } from 'xterm';
import { FitAddon } from 'xterm-addon-fit';
import { WebLinksAddon } from 'xterm-addon-web-links';

export class TerminalManager {
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

        // Create terminal container
        const terminalContainer = document.createElement('div');
        terminalContainer.className = 'terminal-instance';
        terminalContainer.id = terminalId;

        // Create xterm instance
        const terminal = new Terminal({
            cursorBlink: true,
            fontSize: 13,
            fontFamily: 'JetBrains Mono, monospace',
            theme: {
                background: '#000000',
                foreground: '#e4e4e7',
                cursor: '#8b5cf6',
                cursorAccent: '#000000',
                selection: 'rgba(139, 92, 246, 0.3)',
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
        const fitAddon = new FitAddon();
        const webLinksAddon = new WebLinksAddon();

        terminal.loadAddon(fitAddon);
        terminal.loadAddon(webLinksAddon);

        // Open terminal
        terminal.open(terminalContainer);
        fitAddon.fit();

        // Add welcome message
        terminal.writeln('\x1b[1;35mSafeCode IDE Terminal\x1b[0m');
        terminal.writeln('Type commands here...\n');

        // For web mode, create a simple shell simulation
        if (!this.ide.isElectron) {
            this.setupWebTerminal(terminal);
        }

        // Add to panel
        panelContent.appendChild(terminalContainer);

        // Store terminal
        this.terminals.set(terminalId, {
            terminal,
            element: terminalContainer,
            fitAddon
        });

        this.activeTerminal = terminalId;

        // Show terminal panel
        const panel = document.getElementById('bottomPanel');
        if (panel) {
            panel.style.display = 'flex';
        }

        // Handle resize
        window.addEventListener('resize', () => {
            fitAddon.fit();
        });

        return terminalId;
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
        // TODO: Implement split terminal
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
                this.activeTerminal = Array.from(this.terminals.keys())[0];
            }
        }
    }

    resizeTerminals() {
        this.terminals.forEach(({ fitAddon }) => {
            fitAddon.fit();
        });
    }
}
