import React, { useEffect, useRef } from 'react';
import { Terminal as XTerm } from 'xterm';
import { FitAddon } from 'xterm-addon-fit';
import { WebLinksAddon } from 'xterm-addon-web-links';
import 'xterm/css/xterm.css';
import { useIDE } from '../../contexts/IDEContext';
import './Terminal.css';

const Terminal: React.FC = () => {
  const terminalRef = useRef<HTMLDivElement>(null);
  const terminalInstanceRef = useRef<XTerm | null>(null);
  const fitAddonRef = useRef<FitAddon | null>(null);
  const { isElectron } = useIDE();

  useEffect(() => {
    if (!terminalRef.current) return;

    const terminal = new XTerm({
      cursorBlink: true,
      fontSize: 13,
      fontFamily: 'JetBrains Mono, Consolas, monospace',
      theme: {
        background: '#000000',
        foreground: '#e4e4e7',
        cursor: '#ffffff',
        selection: 'rgba(255, 255, 255, 0.3)'
      }
    });

    const fitAddon = new FitAddon();
    const webLinksAddon = new WebLinksAddon();

    terminal.loadAddon(fitAddon);
    terminal.loadAddon(webLinksAddon);

    terminal.open(terminalRef.current);
    fitAddon.fit();

    terminalInstanceRef.current = terminal;
    fitAddonRef.current = fitAddon;

    if (isElectron && (window as any).electronAPI?.terminal) {
      setupPTYTerminal(terminal);
    } else {
      setupWebTerminal(terminal);
    }

    const handleResize = () => {
      fitAddon.fit();
    };

    window.addEventListener('resize', handleResize);

    return () => {
      window.removeEventListener('resize', handleResize);
      terminal.dispose();
    };
  }, [isElectron]);

  const setupPTYTerminal = async (terminal: XTerm) => {
    try {
      const terminalId = `terminal-${Date.now()}`;
      const result = await (window as any).electronAPI.terminal.create(terminalId);
      
      if (result.success) {
        terminal.onData(data => {
          (window as any).electronAPI.terminal.write(terminalId, data);
        });

        if ((window as any).electronAPI.terminal.onData) {
          (window as any).electronAPI.terminal.onData(terminalId, (data: string) => {
            terminal.write(data);
          });
        }
      }
    } catch (error) {
      console.error('Error setting up PTY terminal:', error);
      setupWebTerminal(terminal);
    }
  };

  const setupWebTerminal = (terminal: XTerm) => {
    let currentLine = '';
    const prompt = '\x1b[1;32m$\x1b[0m ';

    terminal.writeln('\x1b[1;35mSafeCode IDE Terminal (Web Mode)\x1b[0m');
    terminal.writeln('Type "help" for available commands.\n');
    terminal.write(prompt);

    terminal.onData(data => {
      const charCode = data.charCodeAt(0);

      if (data === '\r' || data === '\n') {
        terminal.write('\r\n');
        executeCommand(terminal, currentLine.trim());
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
  };

  const executeCommand = (terminal: XTerm, command: string) => {
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
        break;
      case 'date':
        terminal.writeln(new Date().toString());
        break;
      default:
        terminal.writeln(`\x1b[1;31mCommand not found: ${command}\x1b[0m`);
        terminal.writeln('Type "help" for available commands.');
    }
  };

  return (
    <div className="ide-terminal">
      <div className="terminal-header">
        <span>Terminal</span>
      </div>
      <div ref={terminalRef} className="terminal-container"></div>
    </div>
  );
};

export default Terminal;



