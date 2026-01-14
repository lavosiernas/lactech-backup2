import type { FileNode } from '@/types/ide';

interface CommandResult {
  type: 'output' | 'error' | 'info';
  content: string;
  exitCode?: number;
}

interface TerminalState {
  currentDirectory: string;
  files: FileNode[];
  environment: Record<string, string>;
}

// Helper functions
const findNodeByPath = (files: FileNode[], path: string): FileNode | null => {
  if (path === '/' || path === '') {
    return { id: 'root', name: '/', type: 'folder', path: '/', children: files };
  }

  const parts = path.split('/').filter(p => p);
  if (parts.length === 0) {
    return { id: 'root', name: '/', type: 'folder', path: '/', children: files };
  }

  let current: FileNode | null = { id: 'root', name: '/', type: 'folder', path: '/', children: files };

  for (const part of parts) {
    if (!current || current.type !== 'folder' || !current.children) {
      return null;
    }
    const found = current.children.find(child => child.name === part);
    if (!found) return null;
    current = found;
  }

  return current;
};

const getAbsolutePath = (currentDir: string, path: string): string => {
  if (path.startsWith('/')) return path;
  if (currentDir === '/') return `/${path}`;
  return `${currentDir}/${path}`;
};

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return `${bytes}B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)}KB`;
  return `${(bytes / 1024 / 1024).toFixed(1)}MB`;
};

const formatDate = (date: Date): string => {
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Command implementations
export const executeCommand = async (
  command: string,
  state: TerminalState
): Promise<CommandResult> => {
  const trimmed = command.trim();
  if (!trimmed) {
    return { type: 'output', content: '', exitCode: 0 };
  }

  const parts = trimmed.split(/\s+/);
  const cmd = parts[0].toLowerCase();
  const args = parts.slice(1);

  try {
    switch (cmd) {
      case 'ls':
      case 'dir':
        return handleLs(state, args);
      
      case 'cd':
        return handleCd(state, args);
      
      case 'pwd':
        return { type: 'output', content: state.currentDirectory || '/', exitCode: 0 };
      
      case 'cat':
      case 'type':
        return handleCat(state, args);
      
      case 'mkdir':
        return handleMkdir(state, args);
      
      case 'touch':
        return handleTouch(state, args);
      
      case 'rm':
      case 'del':
        return handleRm(state, args);
      
      case 'rmdir':
        return handleRmdir(state, args);
      
      case 'echo':
        return { type: 'output', content: args.join(' '), exitCode: 0 };
      
      case 'clear':
      case 'cls':
        return { type: 'output', content: '\x1b[2J\x1b[H', exitCode: 0 };
      
      case 'date':
        return { type: 'output', content: new Date().toString(), exitCode: 0 };
      
      case 'whoami':
        return { type: 'output', content: state.environment.USER || 'developer', exitCode: 0 };
      
      case 'env':
      case 'printenv':
        return handleEnv(state);
      
      case 'export':
        return handleExport(state, args);
      
      case 'node':
        return handleNode(args);
      
      case 'npm':
        return handleNpm(args);
      
      case 'git':
        return handleGit(state, args);
      
      case 'ps':
        return handlePs();
      
      case 'top':
        return handleTop();
      
      case 'kill':
        return handleKill(args);
      
      case 'grep':
        return handleGrep(state, args);
      
      case 'find':
        return handleFind(state, args);
      
      case 'head':
        return handleHead(state, args);
      
      case 'tail':
        return handleTail(state, args);
      
      case 'wc':
        return handleWc(state, args);
      
      case 'help':
        return handleHelp();
      
      default:
        return { 
          type: 'error', 
          content: `${cmd}: command not found\nType 'help' for available commands.`, 
          exitCode: 127 
        };
    }
  } catch (error) {
    return {
      type: 'error',
      content: error instanceof Error ? error.message : 'Unknown error',
      exitCode: 1
    };
  }
};

const handleLs = (state: TerminalState, args: string[]): CommandResult => {
  const path = args[0] || state.currentDirectory || '/';
  const absolutePath = getAbsolutePath(state.currentDirectory || '/', path);
  const node = findNodeByPath(state.files, absolutePath);

  if (!node) {
    return { type: 'error', content: `ls: cannot access '${path}': No such file or directory`, exitCode: 1 };
  }

  if (node.type === 'file') {
    return { type: 'error', content: `ls: cannot access '${path}': Not a directory`, exitCode: 1 };
  }

  const children = node.children || [];
  if (children.length === 0) {
    return { type: 'output', content: '', exitCode: 0 };
  }

  const longFormat = args.includes('-l') || args.includes('--long');
  
  if (longFormat) {
    const lines = children.map(child => {
      const type = child.type === 'folder' ? 'd' : '-';
      const size = child.type === 'file' ? (child.content?.length || 0) : 0;
      const date = new Date();
      return `${type}rw-r--r--  1 ${state.environment.USER || 'user'} ${state.environment.USER || 'user'} ${size.toString().padStart(10)} ${formatDate(date)} ${child.name}`;
    });
    return { type: 'output', content: lines.join('\n'), exitCode: 0 };
  }

  const names = children.map(child => child.name).join('  ');
  return { type: 'output', content: names, exitCode: 0 };
};

const handleCd = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'output', content: '', exitCode: 0 };
  }

  const path = args[0];
  let targetPath: string;

  if (path === '~' || path === '~/' || path === '$HOME') {
    targetPath = '/';
  } else if (path.startsWith('/')) {
    targetPath = path;
  } else if (path === '..') {
    const parts = (state.currentDirectory || '/').split('/').filter(p => p);
    parts.pop();
    targetPath = parts.length > 0 ? `/${parts.join('/')}` : '/';
  } else if (path === '.') {
    return { type: 'output', content: '', exitCode: 0 };
  } else {
    targetPath = getAbsolutePath(state.currentDirectory || '/', path);
  }

  const node = findNodeByPath(state.files, targetPath);
  if (!node) {
    return { type: 'error', content: `cd: no such file or directory: ${path}`, exitCode: 1 };
  }

  if (node.type !== 'folder') {
    return { type: 'error', content: `cd: not a directory: ${path}`, exitCode: 1 };
  }

  // Update state would be handled by the caller
  return { type: 'output', content: '', exitCode: 0 };
};

const handleCat = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'cat: missing file operand', exitCode: 1 };
  }

  const path = args[0];
  const absolutePath = getAbsolutePath(state.currentDirectory || '/', path);
  const node = findNodeByPath(state.files, absolutePath);

  if (!node) {
    return { type: 'error', content: `cat: ${path}: No such file or directory`, exitCode: 1 };
  }

  if (node.type !== 'file') {
    return { type: 'error', content: `cat: ${path}: Is a directory`, exitCode: 1 };
  }

  return { type: 'output', content: node.content || '', exitCode: 0 };
};

const handleMkdir = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'mkdir: missing operand', exitCode: 1 };
  }

  // This would need to be handled by the store to actually create the folder
  return { type: 'info', content: `mkdir: would create directory '${args[0]}' (use IDE file explorer)`, exitCode: 0 };
};

const handleTouch = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'touch: missing file operand', exitCode: 1 };
  }

  // This would need to be handled by the store to actually create the file
  return { type: 'info', content: `touch: would create file '${args[0]}' (use IDE file explorer)`, exitCode: 0 };
};

const handleRm = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'rm: missing operand', exitCode: 1 };
  }

  // This would need to be handled by the store to actually delete the file
  return { type: 'info', content: `rm: would remove '${args[0]}' (use IDE file explorer)`, exitCode: 0 };
};

const handleRmdir = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'rmdir: missing operand', exitCode: 1 };
  }

  return { type: 'info', content: `rmdir: would remove directory '${args[0]}' (use IDE file explorer)`, exitCode: 0 };
};

const handleEnv = (state: TerminalState): CommandResult => {
  const envVars = Object.entries(state.environment)
    .map(([key, value]) => `${key}=${value}`)
    .join('\n');
  return { type: 'output', content: envVars || 'No environment variables set', exitCode: 0 };
};

const handleExport = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'export: missing variable name', exitCode: 1 };
  }

  const [varDef] = args;
  const [key, value] = varDef.split('=');
  if (!key || !value) {
    return { type: 'error', content: 'export: invalid syntax', exitCode: 1 };
  }

  // This would update the environment
  return { type: 'output', content: '', exitCode: 0 };
};

const handleNode = (args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'output', content: 'v20.10.0', exitCode: 0 };
  }

  if (args[0] === '-v' || args[0] === '--version') {
    return { type: 'output', content: 'v20.10.0', exitCode: 0 };
  }

  if (args[0] === '-e') {
    const code = args.slice(1).join(' ');
    try {
      // Safe evaluation (in a real environment, this would execute in a sandbox)
      const result = Function('"use strict"; return (' + code + ')')();
      return { type: 'output', content: String(result), exitCode: 0 };
    } catch (error) {
      return { type: 'error', content: error instanceof Error ? error.message : 'Evaluation error', exitCode: 1 };
    }
  }

  // For file execution, would need to read from filesystem
  return { type: 'info', content: 'Node.js execution requires file system access. Use the IDE to run files.', exitCode: 0 };
};

const handleNpm = (args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'output', content: 'npm <command>\n\nUsage:\n  npm <command> [args]\n\nCommands:\n  install, i\n  uninstall\n  run\n  start\n  test\n  version, v', exitCode: 0 };
  }

  const cmd = args[0].toLowerCase();

  switch (cmd) {
    case '-v':
    case '--version':
      return { type: 'output', content: '10.2.3', exitCode: 0 };
    case 'install':
    case 'i':
      return { type: 'info', content: 'npm install: Use package.json and run from IDE', exitCode: 0 };
    case 'run':
      return { type: 'info', content: 'npm run: Use package.json scripts from IDE', exitCode: 0 };
    default:
      return { type: 'info', content: `npm ${cmd}: Use IDE to manage packages`, exitCode: 0 };
  }
};

const handleGit = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'output', content: 'usage: git [--version] [--help] [-C <path>] [-c name=value]\n           [<command>] [<args>]', exitCode: 0 };
  }

  const cmd = args[0].toLowerCase();

  switch (cmd) {
    case 'status':
      return { type: 'output', content: 'On branch main\nnothing to commit, working tree clean', exitCode: 0 };
    case 'branch':
      return { type: 'output', content: '* main', exitCode: 0 };
    case 'log':
      return { type: 'output', content: 'No commits yet', exitCode: 0 };
    case '--version':
      return { type: 'output', content: 'git version 2.42.0', exitCode: 0 };
    default:
      return { type: 'info', content: `git ${cmd}: Git operations are managed by the IDE`, exitCode: 0 };
  }
};

const handlePs = (): CommandResult => {
  const processes = [
    { pid: 1, name: 'node', cpu: '0.0', mem: '2.5', time: '00:00:10', command: 'node --version' },
    { pid: 2, name: 'vite', cpu: '1.2', mem: '45.3', time: '00:05:23', command: 'vite' },
    { pid: 3, name: 'chrome', cpu: '5.8', mem: '120.5', time: '01:23:45', command: 'chrome --remote-debugging-port' }
  ];

  const header = '  PID TTY          TIME CMD';
  const lines = processes.map(p => `  ${p.pid.toString().padStart(5)} ?         ${p.time} ${p.command}`);
  
  return { type: 'output', content: [header, ...lines].join('\n'), exitCode: 0 };
};

const handleTop = (): CommandResult => {
  return { type: 'info', content: 'top: Use system task manager for real-time process monitoring', exitCode: 0 };
};

const handleKill = (args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'kill: usage: kill [-s sigspec | -n signum | -sigspec] pid | jobspec ... or kill -l [sigspec]', exitCode: 1 };
  }

  return { type: 'info', content: `kill: Process management requires system access`, exitCode: 0 };
};

const handleGrep = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length < 2) {
    return { type: 'error', content: 'grep: missing pattern or file', exitCode: 1 };
  }

  const pattern = args[0];
  const filePath = args[1];
  const absolutePath = getAbsolutePath(state.currentDirectory || '/', filePath);
  const node = findNodeByPath(state.files, absolutePath);

  if (!node || node.type !== 'file') {
    return { type: 'error', content: `grep: ${filePath}: No such file or directory`, exitCode: 1 };
  }

  const content = node.content || '';
  const regex = new RegExp(pattern, 'g');
  const matches = content.split('\n')
    .map((line, index) => ({ line, number: index + 1 }))
    .filter(({ line }) => regex.test(line))
    .map(({ line, number }) => `${number}:${line}`);

  return { type: 'output', content: matches.join('\n') || 'No matches found', exitCode: 0 };
};

const handleFind = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length < 2) {
    return { type: 'error', content: 'find: missing path or expression', exitCode: 1 };
  }

  return { type: 'info', content: 'find: Use IDE file explorer to search files', exitCode: 0 };
};

const handleHead = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'head: missing file operand', exitCode: 1 };
  }

  const lines = args.includes('-n') ? parseInt(args[args.indexOf('-n') + 1]) || 10 : 10;
  const filePath = args.filter(a => a !== '-n' && !args.includes('-n') || args.indexOf(a) !== args.indexOf('-n') + 1)[0];
  const absolutePath = getAbsolutePath(state.currentDirectory || '/', filePath);
  const node = findNodeByPath(state.files, absolutePath);

  if (!node || node.type !== 'file') {
    return { type: 'error', content: `head: ${filePath}: No such file or directory`, exitCode: 1 };
  }

  const content = (node.content || '').split('\n').slice(0, lines).join('\n');
  return { type: 'output', content, exitCode: 0 };
};

const handleTail = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'tail: missing file operand', exitCode: 1 };
  }

  const lines = args.includes('-n') ? parseInt(args[args.indexOf('-n') + 1]) || 10 : 10;
  const filePath = args.filter(a => a !== '-n' && !args.includes('-n') || args.indexOf(a) !== args.indexOf('-n') + 1)[0];
  const absolutePath = getAbsolutePath(state.currentDirectory || '/', filePath);
  const node = findNodeByPath(state.files, absolutePath);

  if (!node || node.type !== 'file') {
    return { type: 'error', content: `tail: ${filePath}: No such file or directory`, exitCode: 1 };
  }

  const content = (node.content || '').split('\n').slice(-lines).join('\n');
  return { type: 'output', content, exitCode: 0 };
};

const handleWc = (state: TerminalState, args: string[]): CommandResult => {
  if (args.length === 0) {
    return { type: 'error', content: 'wc: missing file operand', exitCode: 1 };
  }

  const filePath = args[0];
  const absolutePath = getAbsolutePath(state.currentDirectory || '/', filePath);
  const node = findNodeByPath(state.files, absolutePath);

  if (!node || node.type !== 'file') {
    return { type: 'error', content: `wc: ${filePath}: No such file or directory`, exitCode: 1 };
  }

  const content = node.content || '';
  const lines = content.split('\n').length;
  const words = content.split(/\s+/).filter(w => w).length;
  const bytes = content.length;

  return { type: 'output', content: `  ${lines}  ${words} ${bytes} ${filePath}`, exitCode: 0 };
};

const handleHelp = (): CommandResult => {
  const helpText = `Available commands:

File Operations:
  ls, dir              - List directory contents
  cd <path>            - Change directory
  pwd                  - Print working directory
  cat <file>           - Display file contents
  head <file>          - Display first lines of file
  tail <file>          - Display last lines of file
  grep <pattern> <file> - Search for pattern in file
  wc <file>            - Count lines, words, bytes

System:
  ps                   - List processes
  top                  - Display processes
  kill <pid>           - Kill process
  date                 - Show current date
  whoami               - Show current user
  env, printenv        - Show environment variables
  export VAR=value     - Set environment variable

Development:
  node [options]       - Node.js runtime
  npm [command]        - Node package manager
  git [command]        - Git version control

Other:
  echo <text>          - Print text
  clear, cls           - Clear terminal
  help                 - Show this help message
`;

  return { type: 'output', content: helpText, exitCode: 0 };
};

