import { create } from 'zustand';
import type { FileNode, EditorTab, TerminalInstance, GitStatus, IDESettings, PanelType, PreviewMode, SyntaxColors } from '@/types/ide';

interface IDEState {
  // Files
  files: FileNode[];
  setFiles: (files: FileNode[]) => void;
  toggleFolder: (path: string) => void;
  createFile: (parentPath: string, name: string) => void;
  createFolder: (parentPath: string, name: string) => void;
  deleteFile: (path: string) => void;
  renameFile: (path: string, newName: string) => void;
  addFileToRoot: (file: FileNode) => void;
  
  // Tabs
  tabs: EditorTab[];
  activeTabId: string | null;
  openFile: (file: FileNode) => void;
  closeTab: (tabId: string) => void;
  setActiveTab: (tabId: string) => void;
  updateTabContent: (tabId: string, content: string) => void;
  updateCursorPosition: (tabId: string, line: number, column: number) => void;
  saveTab: (tabId: string) => void;
  
  // Terminal
  terminals: TerminalInstance[];
  activeTerminalId: string | null;
  addTerminal: () => void;
  removeTerminal: (id: string) => void;
  setActiveTerminal: (id: string) => void;
  addTerminalLine: (terminalId: string, line: { type: 'input' | 'output' | 'error' | 'info'; content: string }) => void;
  clearTerminal: (terminalId: string) => void;
  
  // Panels
  sidebarOpen: boolean;
  terminalOpen: boolean;
  previewOpen: boolean;
  activeSidebarPanel: PanelType;
  toggleSidebar: () => void;
  toggleTerminal: () => void;
  togglePreview: () => void;
  setSidebarPanel: (panel: PanelType) => void;
  
  // Preview
  previewMode: PreviewMode;
  setPreviewMode: (mode: PreviewMode) => void;
  
  // Git
  gitStatus: GitStatus;
  setGitStatus: (status: GitStatus) => void;
  stageFile: (file: string) => void;
  unstageFile: (file: string) => void;
  commitChanges: (message: string) => void;
  
  // Settings
  settings: IDESettings;
  updateSettings: (settings: Partial<IDESettings>) => void;
  
  // Command Palette
  commandPaletteOpen: boolean;
  toggleCommandPalette: () => void;
  
  // Welcome
  showWelcome: boolean;
  setShowWelcome: (show: boolean) => void;
  
  // Find/Replace
  findReplaceOpen: boolean;
  toggleFindReplace: () => void;
}

const defaultFiles: FileNode[] = [
  {
    id: '1',
    name: 'src',
    type: 'folder',
    path: '/src',
    isExpanded: true,
    children: [
      {
        id: '2',
        name: 'components',
        type: 'folder',
        path: '/src/components',
        isExpanded: false,
        children: [
          {
            id: '3',
            name: 'Button.tsx',
            type: 'file',
            path: '/src/components/Button.tsx',
            language: 'typescript',
            content: `import React from 'react';

interface ButtonProps {
  children: React.ReactNode;
  variant?: 'primary' | 'secondary';
  onClick?: () => void;
}

export const Button: React.FC<ButtonProps> = ({ 
  children, 
  variant = 'primary',
  onClick 
}) => {
  return (
    <button
      className={\`btn btn-\${variant}\`}
      onClick={onClick}
    >
      {children}
    </button>
  );
};`
          },
          {
            id: '4',
            name: 'Header.tsx',
            type: 'file',
            path: '/src/components/Header.tsx',
            language: 'typescript',
            content: `import React from 'react';
import { Button } from './Button';

export const Header: React.FC = () => {
  return (
    <header className="header">
      <h1>SAFECODE IDE</h1>
      <nav>
        <Button variant="secondary">Settings</Button>
      </nav>
    </header>
  );
};`
          }
        ]
      },
      {
        id: '5',
        name: 'App.tsx',
        type: 'file',
        path: '/src/App.tsx',
        language: 'typescript',
        content: `import React from 'react';
import { Header } from './components/Header';
import './styles/main.css';

function App() {
  return (
    <div className="app">
      <Header />
      <main>
        <h2>Welcome to SAFECODE</h2>
        <p>Your modern code editor</p>
      </main>
    </div>
  );
}

export default App;`
      },
      {
        id: '6',
        name: 'index.tsx',
        type: 'file',
        path: '/src/index.tsx',
        language: 'typescript',
        content: `import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';

const root = ReactDOM.createRoot(
  document.getElementById('root') as HTMLElement
);

root.render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);`
      },
      {
        id: '7',
        name: 'styles',
        type: 'folder',
        path: '/src/styles',
        isExpanded: false,
        children: [
          {
            id: '8',
            name: 'main.css',
            type: 'file',
            path: '/src/styles/main.css',
            language: 'css',
            content: `/* Main Styles */
:root {
  --primary: #3b82f6;
  --secondary: #64748b;
  --background: #0f172a;
  --foreground: #f8fafc;
}

body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: var(--background);
  color: var(--foreground);
}

.app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.header {
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: var(--primary);
  color: white;
}

.btn-secondary {
  background: var(--secondary);
  color: white;
}`
          }
        ]
      }
    ]
  },
  {
    id: '9',
    name: 'public',
    type: 'folder',
    path: '/public',
    isExpanded: false,
    children: [
      {
        id: '10',
        name: 'index.html',
        type: 'file',
        path: '/public/index.html',
        language: 'html',
        content: `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SAFECODE Project</title>
</head>
<body>
  <div id="root"></div>
  <script type="module" src="/src/index.tsx"></script>
</body>
</html>`
      }
    ]
  },
  {
    id: '11',
    name: 'package.json',
    type: 'file',
    path: '/package.json',
    language: 'json',
    content: `{
  "name": "safecode-project",
  "version": "1.0.0",
  "description": "A SAFECODE IDE Project",
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview"
  },
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0"
  },
  "devDependencies": {
    "@types/react": "^18.2.0",
    "typescript": "^5.0.0",
    "vite": "^5.0.0"
  }
}`
  },
  {
    id: '12',
    name: 'README.md',
    type: 'file',
    path: '/README.md',
    language: 'markdown',
    content: `# SAFECODE Project

Welcome to your new project created with SAFECODE IDE!

## Getting Started

\`\`\`bash
npm install
npm run dev
\`\`\`

## Features

- ⚡ Fast development with Vite
- 🎨 Modern React with TypeScript
- 🔧 Easy to customize

## Documentation

Check out the [SAFECODE docs](https://safecode.dev/docs) for more info.
`
  }
];

const defaultSyntaxColors: SyntaxColors = {
  comment: '6b7280',
  keyword: '60a5fa',
  string: '4ade80',
  number: 'fb923c',
  type: 'fbbf24',
  function: '60a5fa',
  variable: '38bdf8',
  operator: 'f472b6',
};

const defaultSettings: IDESettings = {
  fontSize: 14,
  tabSize: 2,
  theme: 'dark',
  autoSave: false,
  wordWrap: true,
  minimap: true,
  syntaxColors: defaultSyntaxColors,
  activeTheme: 'default',
};

export const useIDEStore = create<IDEState>((set, get) => ({
  // Files
  files: [], // Começa vazio - usuário pode abrir uma pasta ou criar arquivos
  setFiles: (files) => set({ files }),
  toggleFolder: (path) => set((state) => ({
    files: toggleFolderRecursive(state.files, path)
  })),
  createFile: (parentPath, name) => set((state) => {
    const newFile: FileNode = {
      id: `file-${Date.now()}`,
      name,
      type: 'file',
      path: `${parentPath}/${name}`,
      content: '',
      language: getLanguageFromName(name)
    };
    return {
      files: addNodeToPath(state.files, parentPath, newFile)
    };
  }),
  createFolder: (parentPath, name) => set((state) => {
    const newFolder: FileNode = {
      id: `folder-${Date.now()}`,
      name,
      type: 'folder',
      path: `${parentPath}/${name}`,
      isExpanded: false,
      children: []
    };
    return {
      files: addNodeToPath(state.files, parentPath, newFolder)
    };
  }),
  deleteFile: (path) => set((state) => ({
    files: removeNodeByPath(state.files, path)
  })),
  renameFile: (path, newName) => set((state) => ({
    files: renameNodeByPath(state.files, path, newName)
  })),
  addFileToRoot: (file) => set((state) => ({
    files: [...state.files, file]
  })),
  
  // Tabs
  tabs: [],
  activeTabId: null,
  openFile: (file) => {
    if (file.type !== 'file') return;
    
    const { tabs } = get();
    const existingTab = tabs.find(t => t.path === file.path);
    
    if (existingTab) {
      set({ activeTabId: existingTab.id });
    } else {
      const newTab: EditorTab = {
        id: `tab-${Date.now()}`,
        name: file.name,
        path: file.path,
        content: file.content || '',
        language: file.language || 'plaintext',
        isDirty: false,
        cursorPosition: { line: 1, column: 1 }
      };
      set({
        tabs: [...tabs, newTab],
        activeTabId: newTab.id,
        showWelcome: false
      });
    }
  },
  closeTab: (tabId) => set((state) => {
    const newTabs = state.tabs.filter(t => t.id !== tabId);
    const newActiveId = state.activeTabId === tabId
      ? newTabs[newTabs.length - 1]?.id || null
      : state.activeTabId;
    return { tabs: newTabs, activeTabId: newActiveId };
  }),
  setActiveTab: (tabId) => set({ activeTabId: tabId }),
  updateTabContent: (tabId, content) => set((state) => ({
    tabs: state.tabs.map(t => 
      t.id === tabId ? { ...t, content, isDirty: true } : t
    )
  })),
  updateCursorPosition: (tabId, line, column) => set((state) => ({
    tabs: state.tabs.map(t => 
      t.id === tabId ? { ...t, cursorPosition: { line, column } } : t
    )
  })),
  saveTab: (tabId) => set((state) => ({
    tabs: state.tabs.map(t => 
      t.id === tabId ? { ...t, isDirty: false } : t
    )
  })),
  
  // Terminal
  terminals: [{ 
    id: 'terminal-1', 
    name: 'Terminal 1', 
    history: [
      { type: 'info', content: 'Terminal ready. Open a folder to start working.', timestamp: new Date() }
    ],
    currentInput: '' 
  }],
  activeTerminalId: 'terminal-1',
  addTerminal: () => set((state) => {
    const id = `terminal-${Date.now()}`;
    return {
      terminals: [...state.terminals, {
        id,
        name: `Terminal ${state.terminals.length + 1}`,
        history: [
          { type: 'info', content: 'Terminal ready. Open a folder to start working.', timestamp: new Date() }
        ],
        currentInput: ''
      }],
      activeTerminalId: id
    };
  }),
  removeTerminal: (id) => set((state) => ({
    terminals: state.terminals.filter(t => t.id !== id),
    activeTerminalId: state.activeTerminalId === id 
      ? state.terminals[0]?.id || null 
      : state.activeTerminalId
  })),
  setActiveTerminal: (id) => set({ activeTerminalId: id }),
  addTerminalLine: (terminalId, line) => set((state) => ({
    terminals: state.terminals.map(t => 
      t.id === terminalId 
        ? { ...t, history: [...t.history, { ...line, timestamp: new Date() }] }
        : t
    )
  })),
  clearTerminal: (terminalId) => set((state) => ({
    terminals: state.terminals.map(t => 
      t.id === terminalId 
        ? { ...t, history: [] }
        : t
    )
  })),
  
  // Panels
  sidebarOpen: true,
  terminalOpen: false,
  previewOpen: false,
  activeSidebarPanel: 'explorer',
  toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
  toggleTerminal: () => set((state) => ({ terminalOpen: !state.terminalOpen })),
  togglePreview: () => set((state) => ({ previewOpen: !state.previewOpen })),
  setSidebarPanel: (panel) => set({ activeSidebarPanel: panel, sidebarOpen: true }),
  
  // Preview
  previewMode: 'desktop',
  setPreviewMode: (mode) => set({ previewMode: mode }),
  
  // Git
  gitStatus: {
    branch: '',
    staged: [],
    modified: [],
    untracked: []
  },
  setGitStatus: (status) => set({ gitStatus: status }),
  stageFile: (file) => set((state) => {
    const { modified, untracked, staged } = state.gitStatus;
    if (modified.includes(file)) {
      return {
        gitStatus: {
          ...state.gitStatus,
          modified: modified.filter(f => f !== file),
          staged: [...staged, file]
        }
      };
    }
    if (untracked.includes(file)) {
      return {
        gitStatus: {
          ...state.gitStatus,
          untracked: untracked.filter(f => f !== file),
          staged: [...staged, file]
        }
      };
    }
    return state;
  }),
  unstageFile: (file) => set((state) => {
    const { staged, modified } = state.gitStatus;
    if (staged.includes(file)) {
      return {
        gitStatus: {
          ...state.gitStatus,
          staged: staged.filter(f => f !== file),
          modified: [...modified, file]
        }
      };
    }
    return state;
  }),
  commitChanges: (message) => set((state) => {
    if (state.gitStatus.staged.length === 0) return state;
    
    // Simulate commit - clear staged files
    return {
      gitStatus: {
        ...state.gitStatus,
        staged: [],
        modified: []
      }
    };
  }),
  
  // Settings
  settings: defaultSettings,
  updateSettings: (newSettings) => set((state) => ({
    settings: { ...state.settings, ...newSettings }
  })),
  
  // Command Palette
  commandPaletteOpen: false,
  toggleCommandPalette: () => set((state) => ({ commandPaletteOpen: !state.commandPaletteOpen })),
  
  // Welcome
  showWelcome: true,
  setShowWelcome: (show) => set({ showWelcome: show }),
  
  // Find/Replace
  findReplaceOpen: false,
  toggleFindReplace: () => set((state) => ({ findReplaceOpen: !state.findReplaceOpen }))
}));

function toggleFolderRecursive(nodes: FileNode[], path: string): FileNode[] {
  return nodes.map(node => {
    if (node.path === path && node.type === 'folder') {
      return { ...node, isExpanded: !node.isExpanded };
    }
    if (node.children) {
      return { ...node, children: toggleFolderRecursive(node.children, path) };
    }
    return node;
  });
}

function addNodeToPath(nodes: FileNode[], parentPath: string, newNode: FileNode): FileNode[] {
  return nodes.map(node => {
    if (node.path === parentPath && node.type === 'folder') {
      return {
        ...node,
        isExpanded: true,
        children: [...(node.children || []), newNode]
      };
    }
    if (node.children) {
      return { ...node, children: addNodeToPath(node.children, parentPath, newNode) };
    }
    return node;
  });
}

function removeNodeByPath(nodes: FileNode[], path: string): FileNode[] {
  return nodes
    .filter(node => node.path !== path)
    .map(node => {
      if (node.children) {
        return { ...node, children: removeNodeByPath(node.children, path) };
      }
      return node;
    });
}

function renameNodeByPath(nodes: FileNode[], path: string, newName: string): FileNode[] {
  return nodes.map(node => {
    if (node.path === path) {
      const pathParts = path.split('/');
      pathParts[pathParts.length - 1] = newName;
      return {
        ...node,
        name: newName,
        path: pathParts.join('/')
      };
    }
    if (node.children) {
      return { ...node, children: renameNodeByPath(node.children, path, newName) };
    }
    return node;
  });
}

function getLanguageFromName(name: string): string {
  const ext = name.split('.').pop()?.toLowerCase();
  const langMap: Record<string, string> = {
    'ts': 'typescript',
    'tsx': 'typescript',
    'js': 'javascript',
    'jsx': 'javascript',
    'json': 'json',
    'html': 'html',
    'css': 'css',
    'md': 'markdown',
    'py': 'python',
    'java': 'java',
    'cpp': 'cpp',
    'c': 'c',
    'go': 'go',
    'rs': 'rust',
    'php': 'php',
    'rb': 'ruby',
    'sh': 'shell',
    'yml': 'yaml',
    'yaml': 'yaml',
  };
  return langMap[ext || ''] || 'plaintext';
}
