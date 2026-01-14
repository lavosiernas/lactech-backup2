import { useState, useRef, useEffect } from 'react';
import { Plus, X, Terminal as TerminalIcon, Trash2 } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import { executeCommand } from '@/services/terminalCommands';

interface TerminalState {
  currentDirectory: string;
  environment: Record<string, string>;
}

export const Terminal: React.FC = () => {
  const { 
    terminals, 
    activeTerminalId, 
    addTerminal, 
    removeTerminal, 
    setActiveTerminal,
    addTerminalLine,
    files,
    clearTerminal: clearTerminalStore
  } = useIDEStore();
  
  const [input, setInput] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);
  const outputRef = useRef<HTMLDivElement>(null);
  const [terminalStates, setTerminalStates] = useState<Map<string, TerminalState>>(new Map());

  const activeTerminal = terminals.find(t => t.id === activeTerminalId);

  // Initialize terminal state
  useEffect(() => {
    if (activeTerminalId && !terminalStates.has(activeTerminalId)) {
      setTerminalStates(prev => {
        const newMap = new Map(prev);
        newMap.set(activeTerminalId, {
          currentDirectory: '/',
          environment: {
            USER: 'developer',
            HOME: '/',
            PATH: '/usr/bin:/bin',
            PWD: '/'
          }
        });
        return newMap;
      });
    }
  }, [activeTerminalId, terminalStates]);

  useEffect(() => {
    if (outputRef.current) {
      outputRef.current.scrollTop = outputRef.current.scrollHeight;
    }
  }, [activeTerminal?.history]);

  const handleCommand = async (command: string) => {
    if (!activeTerminalId || !command.trim()) return;

    const state = terminalStates.get(activeTerminalId) || {
      currentDirectory: '/',
      environment: { USER: 'developer', HOME: '/', PATH: '/usr/bin:/bin', PWD: '/' }
    };

    // Handle clear command
    if (command.trim().toLowerCase() === 'clear' || command.trim().toLowerCase() === 'cls') {
      clearTerminalStore(activeTerminalId);
      setInput('');
      return;
    }

    // Handle cd command separately to update state
    const parts = command.trim().split(/\s+/);
    if (parts[0].toLowerCase() === 'cd' && parts.length > 0) {
      const path = parts[1] || '/';
      let targetPath: string;

      if (path === '~' || path === '~/' || path === '$HOME') {
        targetPath = '/';
      } else if (path.startsWith('/')) {
        targetPath = path;
      } else if (path === '..') {
        const dirParts = (state.currentDirectory || '/').split('/').filter(p => p);
        dirParts.pop();
        targetPath = dirParts.length > 0 ? `/${dirParts.join('/')}` : '/';
      } else if (path === '.') {
        setInput('');
        return;
      } else {
        targetPath = state.currentDirectory === '/' ? `/${path}` : `${state.currentDirectory}/${path}`;
      }

      // Check if directory exists
      const findNodeByPath = (files: any[], path: string): any => {
        if (path === '/' || path === '') {
          return { type: 'folder', children: files };
        }
        const parts = path.split('/').filter(p => p);
        let current: any = { type: 'folder', children: files };
        for (const part of parts) {
          if (!current || current.type !== 'folder' || !current.children) return null;
          const found = current.children.find((child: any) => child.name === part);
          if (!found) return null;
          current = found;
        }
        return current;
      };

      const node = findNodeByPath(files, targetPath);
      if (node && node.type === 'folder') {
        setTerminalStates(prev => {
          const newMap = new Map(prev);
          const newState = { ...state, currentDirectory: targetPath, environment: { ...state.environment, PWD: targetPath } };
          newMap.set(activeTerminalId, newState);
          return newMap;
        });
        addTerminalLine(activeTerminalId, { type: 'input', content: `$ ${command}` });
        setInput('');
        return;
      } else {
        addTerminalLine(activeTerminalId, { type: 'input', content: `$ ${command}` });
        addTerminalLine(activeTerminalId, { type: 'error', content: `cd: no such file or directory: ${path}` });
        setInput('');
        return;
      }
    }

    // Add input line
    addTerminalLine(activeTerminalId, { type: 'input', content: `$ ${command}` });

    // Execute command
    const updatedState = terminalStates.get(activeTerminalId) || state;
    const result = await executeCommand(command, {
      ...updatedState,
      files
    });

    // Handle special output for clear
    if (result.content === '\x1b[2J\x1b[H') {
      clearTerminalStore(activeTerminalId);
      setInput('');
      return;
    }

    // Add output
    if (result.content) {
      addTerminalLine(activeTerminalId, result);
    }

    setInput('');
  };

  const getLineColor = (type: string) => {
    switch (type) {
      case 'input': return 'text-foreground';
      case 'output': return 'text-terminal-foreground';
      case 'error': return 'text-destructive';
      case 'info': return 'text-muted-foreground';
      default: return 'text-foreground';
    }
  };

  return (
    <div className="h-full flex flex-col bg-terminal">
      {/* Terminal tabs */}
      <div className="flex items-center h-7" style={{ backgroundColor: 'hsl(var(--tab))' }}>
        <div className="flex items-center flex-1 overflow-x-auto hide-scrollbar">
          {terminals.map((terminal) => (
            <div
              key={terminal.id}
              onClick={() => setActiveTerminal(terminal.id)}
              className={`flex items-center gap-1.5 px-2 py-1 text-xs cursor-pointer transition-colors ${
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
            onClick={() => {
              if (activeTerminalId) {
                clearTerminalStore(activeTerminalId);
              }
            }}
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
        className="flex-1 overflow-auto hide-scrollbar p-2 font-mono text-xs"
        onClick={() => inputRef.current?.focus()}
      >
        {activeTerminal?.history.map((line, index) => (
          <div key={index} className={`${getLineColor(line.type)} whitespace-pre-wrap`}>
            {line.content}
          </div>
        ))}
        
        {/* Input line */}
        <div className="flex items-center mt-1">
          <span className="text-foreground mr-2">
            {(() => {
              const state = terminalStates.get(activeTerminalId || '');
              const dir = state?.currentDirectory || '/';
              const displayDir = dir === '/' ? '~' : dir.split('/').pop() || '~';
              return `${state?.environment.USER || 'developer'}@safecode:${displayDir}$`;
            })()}
          </span>
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
            className="flex-1 bg-transparent outline-none text-foreground font-mono border-none"
            style={{ 
              outline: 'none',
              border: 'none',
              boxShadow: 'none',
              caretColor: 'hsl(var(--foreground))'
            }}
            autoFocus
          />
        </div>
      </div>
    </div>
  );
};
