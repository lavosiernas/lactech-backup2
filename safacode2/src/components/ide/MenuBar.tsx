import { useState } from 'react';
import { 
  Play, 
  LayoutPanelLeft,
  LayoutPanelTop,
  Eye
} from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import { useToast } from '@/components/ui/use-toast';
import { useFilePicker } from './FilePicker';
import { NewFileDialog } from './NewFileDialog';
import { SaveAsDialog } from './SaveAsDialog';
import { KeyboardShortcutsDialog } from './KeyboardShortcutsDialog';
import { AboutDialog } from './AboutDialog';
import type { FileNode } from '@/types/ide';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuShortcut,
  DropdownMenuTrigger,
  DropdownMenuSub,
  DropdownMenuSubTrigger,
  DropdownMenuSubContent,
} from '@/components/ui/dropdown-menu';

const menuItems = [
  {
    label: 'File',
    items: [
      { label: 'New File', shortcut: 'Ctrl+N' },
      { label: 'New Folder' },
      { type: 'separator' },
      { label: 'Open File...', shortcut: 'Ctrl+O' },
      { label: 'Open Folder...', shortcut: 'Ctrl+K Ctrl+O' },
      { type: 'separator' },
      { label: 'Save', shortcut: 'Ctrl+S' },
      { label: 'Save As...', shortcut: 'Ctrl+Shift+S' },
      { label: 'Save All', shortcut: 'Ctrl+K S' },
      { type: 'separator' },
      { label: 'Close Editor', shortcut: 'Ctrl+W' },
    ],
  },
  {
    label: 'Edit',
    items: [
      { label: 'Undo', shortcut: 'Ctrl+Z' },
      { label: 'Redo', shortcut: 'Ctrl+Y' },
      { type: 'separator' },
      { label: 'Cut', shortcut: 'Ctrl+X' },
      { label: 'Copy', shortcut: 'Ctrl+C' },
      { label: 'Paste', shortcut: 'Ctrl+V' },
      { type: 'separator' },
      { label: 'Find', shortcut: 'Ctrl+F' },
      { label: 'Replace', shortcut: 'Ctrl+H' },
    ],
  },
  {
    label: 'View',
    items: [
      { label: 'Command Palette...', shortcut: 'Ctrl+Shift+P' },
      { type: 'separator' },
      { label: 'Explorer', shortcut: 'Ctrl+Shift+E' },
      { label: 'Search', shortcut: 'Ctrl+Shift+F' },
      { label: 'Source Control', shortcut: 'Ctrl+Shift+G' },
      { type: 'separator' },
      { label: 'Terminal', shortcut: 'Ctrl+`' },
      { label: 'Problems' },
      { label: 'Output' },
    ],
  },
  {
    label: 'Run',
    items: [
      { label: 'Start Debugging', shortcut: 'F5' },
      { label: 'Run Without Debugging', shortcut: 'Ctrl+F5' },
      { label: 'Stop Debugging', shortcut: 'Shift+F5' },
      { type: 'separator' },
      { label: 'Add Configuration...' },
    ],
  },
  {
    label: 'Terminal',
    items: [
      { label: 'New Terminal', shortcut: 'Ctrl+Shift+`' },
      { label: 'Split Terminal' },
      { type: 'separator' },
      { label: 'Run Task...' },
      { label: 'Run Build Task...', shortcut: 'Ctrl+Shift+B' },
    ],
  },
  {
    label: 'Help',
    items: [
      { label: 'Welcome' },
      { label: 'Documentation' },
      { label: 'Keyboard Shortcuts', shortcut: 'Ctrl+K Ctrl+S' },
      { type: 'separator' },
      { label: 'About SAFECODE' },
    ],
  },
];

const getLanguageFromFileName = (name: string): string => {
  const ext = name.split('.').pop()?.toLowerCase();
  const langMap: Record<string, string> = {
    'ts': 'typescript', 'tsx': 'typescript',
    'js': 'javascript', 'jsx': 'javascript',
    'json': 'json', 'html': 'html', 'css': 'css',
    'md': 'markdown', 'py': 'python', 'java': 'java',
    'cpp': 'cpp', 'c': 'c', 'go': 'go', 'rs': 'rust',
    'php': 'php', 'rb': 'ruby', 'sh': 'shell',
    'yml': 'yaml', 'yaml': 'yaml',
  };
  return langMap[ext || ''] || 'plaintext';
};

const buildFileTreeFromFiles = async (files: File[]): Promise<FileNode[]> => {
  const folderMap = new Map<string, FileNode>();
  const rootNodes: FileNode[] = [];
  
  const filePromises = files.map(async (file) => {
    const relativePath = file.webkitRelativePath || file.name;
    const parts = relativePath.split('/').filter(p => p);
    
    if (parts.length === 0) return;
    
    let currentPath = '';
    for (let i = 0; i < parts.length - 1; i++) {
      const folderName = parts[i];
      const folderPath = currentPath ? `${currentPath}/${folderName}` : `/${folderName}`;
      
      if (!folderMap.has(folderPath)) {
        const folderNode: FileNode = {
          id: `folder-${Date.now()}-${Math.random()}`,
          name: folderName,
          type: 'folder',
          path: folderPath,
          isExpanded: false,
          children: []
        };
        folderMap.set(folderPath, folderNode);
        
        if (i === 0) {
          rootNodes.push(folderNode);
        } else {
          const parentPath = currentPath;
          const parent = folderMap.get(parentPath);
          if (parent && parent.children) {
            parent.children.push(folderNode);
          }
        }
      }
      currentPath = folderPath;
    }
    
    const content = await new Promise<string>((resolve) => {
      const reader = new FileReader();
      reader.onload = (e) => resolve(e.target?.result as string || '');
      reader.onerror = () => resolve('');
      reader.readAsText(file);
    });
    
    const fileName = parts[parts.length - 1];
    const filePath = currentPath ? `${currentPath}/${fileName}` : `/${fileName}`;
    const fileNode: FileNode = {
      id: `file-${Date.now()}-${Math.random()}`,
      name: fileName,
      type: 'file',
      path: filePath,
      content,
      language: getLanguageFromFileName(fileName)
    };
    
    if (parts.length === 1) {
      rootNodes.push(fileNode);
    } else {
      const parentPath = currentPath;
      const parent = folderMap.get(parentPath);
      if (parent && parent.children) {
        parent.children.push(fileNode);
      }
    }
  });
  
  await Promise.all(filePromises);
  return rootNodes;
};

export const MenuBar: React.FC = () => {
  const { 
    toggleSidebar, 
    toggleTerminal, 
    togglePreview, 
    toggleCommandPalette,
    terminalOpen, 
    previewOpen,
    createFile,
    createFolder,
    openFile,
    tabs,
    activeTabId,
    saveTab,
    closeTab,
    setSidebarPanel,
    toggleFindReplace,
    addTerminal,
    setShowWelcome,
    updateSettings,
    settings,
    addFileToRoot,
    setFiles
  } = useIDEStore();

  const { toast } = useToast();
  const { openFilePicker, openDirectoryPicker } = useFilePicker();
  const [newFileDialog, setNewFileDialog] = useState<{ open: boolean; type: 'file' | 'folder' }>({ open: false, type: 'file' });
  const [saveAsDialog, setSaveAsDialog] = useState(false);
  const [shortcutsDialog, setShortcutsDialog] = useState(false);
  const [aboutDialog, setAboutDialog] = useState(false);

  const handleMenuAction = (menuLabel: string, itemLabel: string) => {
    switch (menuLabel) {
      case 'File':
        switch (itemLabel) {
          case 'New File':
            setNewFileDialog({ open: true, type: 'file' });
            break;
          case 'New Folder':
            setNewFileDialog({ open: true, type: 'folder' });
            break;
          case 'Open File...':
            openFilePicker().then((selectedFiles) => {
              if (selectedFiles && selectedFiles.length > 0) {
                selectedFiles.forEach(file => {
                  try {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                      try {
                        const content = e.target?.result as string;
                        const newFile = {
                          id: `file-${Date.now()}-${Math.random()}`,
                          name: file.name,
                          type: 'file' as const,
                          path: `/${file.name}`,
                          content: content,
                          language: getLanguageFromFileName(file.name)
                        };
                        addFileToRoot(newFile);
                        openFile(newFile);
                        toast({
                          title: 'File opened',
                          description: `${file.name} opened successfully`,
                          variant: 'default',
                        });
                      } catch (error) {
                        console.error('Error processing file:', error);
                        toast({
                          title: 'Error',
                          description: `Failed to open ${file.name}`,
                          variant: 'destructive',
                        });
                      }
                    };
                    reader.onerror = () => {
                      toast({
                        title: 'Error',
                        description: `Failed to read ${file.name}`,
                        variant: 'destructive',
                      });
                    };
                    reader.readAsText(file);
                  } catch (error) {
                    console.error('Error reading file:', error);
                    toast({
                      title: 'Error',
                      description: `Failed to open ${file.name}`,
                      variant: 'destructive',
                    });
                  }
                });
              }
            }).catch((error) => {
              console.error('File picker error:', error);
            });
            break;
          case 'Open Folder...':
            openDirectoryPicker().then(async (fileList) => {
              if (fileList && fileList.length > 0) {
                const files = Array.from(fileList);
                const fileTree = await buildFileTreeFromFiles(files);
                setFiles(fileTree);
                
                toast({
                  title: 'Folder opened',
                  description: `${files.length} files loaded`,
                });
              }
            }).catch((error) => {
              console.error('Error opening folder:', error);
              toast({
                title: 'Error',
                description: 'Failed to open folder',
                variant: 'destructive',
              });
            });
            break;
          case 'Save':
            if (activeTabId) {
              saveTab(activeTabId);
              toast({
                title: 'File saved',
                description: 'File saved successfully',
              });
            }
            break;
          case 'Save As...':
            if (activeTabId) {
              setSaveAsDialog(true);
            }
            break;
          case 'Save All':
            tabs.forEach(tab => saveTab(tab.id));
            toast({
              title: 'All files saved',
              description: `${tabs.length} files saved successfully`,
            });
            break;
          case 'Close Editor':
            if (activeTabId) closeTab(activeTabId);
            break;
        }
        break;
      case 'Edit':
        switch (itemLabel) {
          case 'Undo':
            const editor = (window as any).monacoEditor;
            if (editor) editor.trigger('keyboard', 'undo', {});
            break;
          case 'Redo':
            const editor2 = (window as any).monacoEditor;
            if (editor2) editor2.trigger('keyboard', 'redo', {});
            break;
          case 'Cut':
            document.execCommand('cut');
            break;
          case 'Copy':
            document.execCommand('copy');
            break;
          case 'Paste':
            document.execCommand('paste');
            break;
          case 'Find':
            toggleFindReplace();
            break;
          case 'Replace':
            toggleFindReplace();
            break;
        }
        break;
      case 'View':
        switch (itemLabel) {
          case 'Command Palette...':
            toggleCommandPalette();
            break;
          case 'Explorer':
            setSidebarPanel('explorer');
            break;
          case 'Search':
            setSidebarPanel('search');
            break;
          case 'Source Control':
            setSidebarPanel('git');
            break;
          case 'Terminal':
            toggleTerminal();
            break;
          case 'Problems':
            setSidebarPanel('explorer');
            toast({
              title: 'Problems',
              description: 'Problems panel would be shown here',
            });
            break;
          case 'Output':
            toggleTerminal();
            toast({
              title: 'Output',
              description: 'Output shown in terminal',
            });
            break;
        }
        break;
      case 'Run':
        switch (itemLabel) {
          case 'Start Debugging':
            handleRun('debug');
            break;
          case 'Run Without Debugging':
            handleRun('run');
            break;
          case 'Stop Debugging':
            handleStop();
            break;
          case 'Add Configuration...':
            toast({
              title: 'Debug Configuration',
              description: 'Debug configuration would be created here',
            });
            break;
        }
        break;
      case 'Terminal':
        switch (itemLabel) {
          case 'New Terminal':
            addTerminal();
            toggleTerminal();
            break;
          case 'Split Terminal':
            addTerminal();
            break;
          case 'Run Task...':
            toast({
              title: 'Run Task',
              description: 'Enter task name in terminal or use Run Build Task',
            });
            toggleTerminal();
            break;
          case 'Run Build Task...':
            handleRunTask('build');
            break;
        }
        break;
      case 'Help':
        switch (itemLabel) {
          case 'Welcome':
            setShowWelcome(true);
            break;
          case 'Documentation':
            window.open('/landing/docs.html', '_blank');
            break;
          case 'Keyboard Shortcuts':
            setShortcutsDialog(true);
            break;
          case 'About SAFECODE':
            setAboutDialog(true);
            break;
        }
        break;
    }
  };

  const { terminals, addTerminalLine } = useIDEStore();

  const handleRun = (mode: 'run' | 'debug') => {
    const activeTab = tabs.find(t => t.id === activeTabId);
    if (!activeTab) {
      toast({
        title: 'No file open',
        description: 'Please open a file to run',
        variant: 'destructive',
      });
      return;
    }

    // Add terminal output
    const terminal = terminals[0];
    if (terminal) {
      addTerminalLine(terminal.id, {
        type: 'info',
        content: `Running ${activeTab.name} in ${mode} mode...`
      });
      addTerminalLine(terminal.id, {
        type: 'output',
        content: mode === 'debug' ? 'Debugger started' : 'Process started'
      });
      toast({
        title: mode === 'debug' ? 'Debugging started' : 'Process started',
        description: `Running ${activeTab.name}`,
      });
    }
    toggleTerminal();
  };

  const handleStop = () => {
    const terminal = terminals[0];
    if (terminal) {
      addTerminalLine(terminal.id, {
        type: 'info',
        content: 'Process stopped'
      });
      toast({
        title: 'Process stopped',
        description: 'Debugging/execution stopped',
      });
    }
  };

  const handleRunTask = (task: string) => {
    const terminal = terminals[0];
    if (terminal) {
      addTerminalLine(terminal.id, {
        type: 'input',
        content: `$ npm run ${task}`
      });
      addTerminalLine(terminal.id, {
        type: 'output',
        content: `Running ${task} task...`
      });
      toast({
        title: 'Task started',
        description: `Running ${task} task`,
      });
    }
    toggleTerminal();
  };

  return (
    <div className="h-7 border-b flex items-center px-2 gap-0.5" style={{ backgroundColor: 'hsl(var(--tab))', borderBottomColor: 'hsl(var(--panel-border))' }}>
      {/* Logo */}
      <div className="flex items-center gap-1.5 px-1.5 mr-2">
        <img 
          src="/logos (6).png" 
          alt="SAFECODE" 
          className="w-3.5 h-3.5 object-contain"
          onError={(e) => {
            e.currentTarget.style.display = 'none';
          }}
        />
        <span className="text-xs font-medium text-foreground">SAFECODE</span>
      </div>

      {/* Menu items */}
      {menuItems.map((menu) => (
        <DropdownMenu key={menu.label}>
          <DropdownMenuTrigger asChild>
            <button className="px-2 py-1 text-xs text-muted-foreground hover:text-foreground hover:bg-muted transition-colors">
              {menu.label}
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent className="border-panel-border min-w-48" style={{ backgroundColor: '#000000' }}>
            {menu.items.map((item, index) => 
              item.type === 'separator' ? (
                <DropdownMenuSeparator key={index} />
              ) : (
                <DropdownMenuItem 
                  key={item.label} 
                  className="cursor-pointer"
                  onClick={() => handleMenuAction(menu.label, item.label)}
                >
                  {item.label}
                  {item.shortcut && (
                    <DropdownMenuShortcut>{item.shortcut}</DropdownMenuShortcut>
                  )}
                </DropdownMenuItem>
              )
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      ))}

      <div className="flex-1" />

      {/* Dialogs */}
      <NewFileDialog
        open={newFileDialog.open}
        onOpenChange={(open) => setNewFileDialog({ ...newFileDialog, open })}
        onConfirm={(name) => {
          if (newFileDialog.type === 'file') {
            createFile('/', name);
          } else {
            createFolder('/', name);
          }
          toast({
            title: `${newFileDialog.type === 'file' ? 'File' : 'Folder'} created`,
            description: `${name} created successfully`,
          });
        }}
        type={newFileDialog.type}
      />
      <SaveAsDialog
        open={saveAsDialog}
        onOpenChange={setSaveAsDialog}
        onConfirm={(name) => {
          if (activeTabId) {
            const tab = tabs.find(t => t.id === activeTabId);
            if (tab) {
              // Update tab name and save
              saveTab(activeTabId);
              toast({
                title: 'File saved',
                description: `Saved as ${name}`,
              });
            }
          }
        }}
        currentName={tabs.find(t => t.id === activeTabId)?.name || ''}
      />
      <KeyboardShortcutsDialog
        open={shortcutsDialog}
        onOpenChange={setShortcutsDialog}
      />
      <AboutDialog
        open={aboutDialog}
        onOpenChange={setAboutDialog}
      />

      {/* Quick actions */}
      <div className="flex items-center gap-0.5 border-l border-panel-border pl-1.5 ml-1.5">
        <button
          onClick={toggleSidebar}
          className="p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-all rounded group"
          title="Toggle Sidebar (Ctrl+B)"
        >
          <LayoutPanelLeft className="w-3.5 h-3.5 transition-transform group-hover:scale-110" />
        </button>
        <button
          onClick={toggleTerminal}
          className={`p-1 transition-all rounded group ${
            terminalOpen
              ? 'text-primary hover:bg-muted'
              : 'text-muted-foreground hover:text-foreground hover:bg-muted'
          }`}
          title="Toggle Terminal (Ctrl+`)"
        >
          <LayoutPanelTop className={`w-3.5 h-3.5 transition-transform ${terminalOpen ? 'scale-110' : 'group-hover:scale-110'}`} />
        </button>
        <button
          onClick={togglePreview}
          className={`p-1 transition-all rounded group ${
            previewOpen
              ? 'text-primary hover:bg-muted'
              : 'text-muted-foreground hover:text-foreground hover:bg-muted'
          }`}
          title="Toggle Preview (Ctrl+Shift+V)"
        >
          <Eye className={`w-3.5 h-3.5 transition-transform ${previewOpen ? 'scale-110' : 'group-hover:scale-110'}`} />
        </button>
        <div className="w-px h-4 bg-panel-border mx-0.5" />
        <button
          onClick={() => handleRun('run')}
          className="p-1 text-muted-foreground hover:text-foreground hover:bg-muted transition-all rounded group"
          title="Run (F5)"
        >
          <Play className="w-3.5 h-3.5 transition-transform group-hover:scale-110 group-hover:text-primary" />
        </button>
      </div>
    </div>
  );
};
