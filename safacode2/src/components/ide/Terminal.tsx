import { useState, useRef, useEffect } from 'react';
import { Plus, X, Terminal as TerminalIcon, Trash2 } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';

export const Terminal: React.FC = () => {
  const { 
    terminals, 
    activeTerminalId, 
    addTerminal, 
    removeTerminal, 
    setActiveTerminal,
    addTerminalLine 
  } = useIDEStore();
  
  const [input, setInput] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);
  const outputRef = useRef<HTMLDivElement>(null);

  const activeTerminal = terminals.find(t => t.id === activeTerminalId);

  useEffect(() => {
    if (outputRef.current) {
      outputRef.current.scrollTop = outputRef.current.scrollHeight;
    }
  }, [activeTerminal?.history]);

  const handleCommand = (command: string) => {
    if (!activeTerminalId || !command.trim()) return;

    // Add input line
    addTerminalLine(activeTerminalId, { type: 'input', content: `$ ${command}` });

    // Process command
    const [cmd, ...args] = command.trim().split(' ');
    let response: { type: 'output' | 'error' | 'info'; content: string };

    switch (cmd.toLowerCase()) {
      case 'help':
        response = {
          type: 'info',
          content: `Available commands:
  help          - Show this help message
  clear         - Clear terminal
  ls            - List files
  pwd           - Print working directory
  echo <text>   - Print text
  date          - Show current date
  whoami        - Show current user
  node -v       - Node version
  npm -v        - NPM version
  git status    - Git status
  git branch    - Show branches`
        };
        break;
      case 'clear':
        // Clear is handled separately
        return;
      case 'ls':
        response = {
          type: 'output',
          content: 'No files loaded. Open a folder to see files.'
        };
        break;
      case 'pwd':
        response = { type: 'output', content: 'No working directory. Open a folder first.' };
        break;
      case 'echo':
        response = { type: 'output', content: args.join(' ') };
        break;
      case 'date':
        response = { type: 'output', content: new Date().toString() };
        break;
      case 'whoami':
        response = { type: 'output', content: 'developer' };
        break;
      case 'node':
        response = { type: 'output', content: 'v20.10.0' };
        break;
      case 'npm':
        response = { type: 'output', content: '10.2.3' };
        break;
      case 'git':
        if (args[0] === 'status') {
          response = {
            type: 'output',
            content: 'Not a git repository. Open a folder with a git repository to use git commands.'
          };
        } else if (args[0] === 'branch') {
          response = { type: 'output', content: 'Not a git repository.' };
        } else {
          response = { type: 'info', content: `git: '${args[0]}' is not a git command.` };
        }
        break;
      default:
        response = { type: 'error', content: `Command not found: ${cmd}` };
    }

    addTerminalLine(activeTerminalId, response);
    setInput('');
  };

  const getLineColor = (type: string) => {
    switch (type) {
      case 'input': return 'text-foreground';
      case 'output': return 'text-terminal-foreground';
      case 'error': return 'text-destructive';
      case 'info': return 'text-primary';
      default: return 'text-foreground';
    }
  };

  return (
    <div className="h-full flex flex-col bg-terminal">
      {/* Terminal tabs */}
      <div className="flex items-center border-b h-7" style={{ backgroundColor: 'hsl(var(--tab))', borderBottomColor: 'hsl(var(--panel-border))' }}>
        <div className="flex items-center flex-1 overflow-x-auto">
          {terminals.map((terminal) => (
            <div
              key={terminal.id}
              onClick={() => setActiveTerminal(terminal.id)}
              className={`flex items-center gap-1.5 px-2 py-1 text-xs cursor-pointer border-r border-panel-border transition-colors ${
                terminal.id === activeTerminalId 
                  ? 'bg-terminal text-foreground' 
                  : 'text-muted-foreground hover:bg-muted'
              }`}
            >
              <TerminalIcon className="w-3 h-3" />
              <span>{terminal.name}</span>
              {terminals.length > 1 && (
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    removeTerminal(terminal.id);
                  }}
                  className="p-0.5 rounded hover:bg-muted"
                >
                  <X className="w-3 h-3" />
                </button>
              )}
            </div>
          ))}
        </div>
        <div className="flex items-center gap-1 px-2">
          <button
            onClick={addTerminal}
            className="p-1 rounded hover:bg-muted transition-colors"
            title="New Terminal"
          >
            <Plus className="w-4 h-4 text-muted-foreground" />
          </button>
          <button
            className="p-1 rounded hover:bg-muted transition-colors"
            title="Clear Terminal"
          >
            <Trash2 className="w-4 h-4 text-muted-foreground" />
          </button>
        </div>
      </div>

      {/* Terminal output */}
      <div 
        ref={outputRef}
        className="flex-1 overflow-auto p-2 font-mono text-xs scrollbar-thin"
        onClick={() => inputRef.current?.focus()}
      >
        {activeTerminal?.history.map((line, index) => (
          <div key={index} className={`${getLineColor(line.type)} whitespace-pre-wrap`}>
            {line.content}
          </div>
        ))}
        
        {/* Input line */}
        <div className="flex items-center mt-1">
          <span className="text-primary mr-2">$</span>
          <input
            ref={inputRef}
            type="text"
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === 'Enter') {
                handleCommand(input);
              }
            }}
            className="flex-1 bg-transparent outline-none text-foreground font-mono"
            autoFocus
          />
        </div>
      </div>
    </div>
  );
};
