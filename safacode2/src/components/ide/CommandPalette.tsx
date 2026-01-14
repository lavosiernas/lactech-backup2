import { useEffect, useState, useMemo } from 'react';
import { Search, FileCode2, Settings, Terminal, GitBranch, Play, FolderOpen } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import { NewFileDialog } from './NewFileDialog';
import { useToast } from '@/components/ui/use-toast';
import type { Command } from '@/types/ide';

export const CommandPalette: React.FC = () => {
  const { 
    commandPaletteOpen, 
    toggleCommandPalette, 
    toggleSidebar, 
    toggleTerminal, 
    togglePreview,
    setSidebarPanel,
    files,
    openFile,
    createFile,
    tabs,
    activeTabId,
    saveTab,
    toggleFindReplace
  } = useIDEStore();
  
  const { toast } = useToast();
  const [search, setSearch] = useState('');
  const [selectedIndex, setSelectedIndex] = useState(0);
  const [newFileDialog, setNewFileDialog] = useState(false);

  const commands: Command[] = useMemo(() => [
    { 
      id: 'toggle-sidebar', 
      label: 'Toggle Sidebar', 
      shortcut: 'Ctrl+B', 
      action: toggleSidebar,
      category: 'View'
    },
    { 
      id: 'toggle-terminal', 
      label: 'Toggle Terminal', 
      shortcut: 'Ctrl+`', 
      action: toggleTerminal,
      category: 'View'
    },
    { 
      id: 'toggle-preview', 
      label: 'Toggle Preview', 
      shortcut: 'Ctrl+Shift+V', 
      action: togglePreview,
      category: 'View'
    },
    { 
      id: 'show-explorer', 
      label: 'Show Explorer', 
      action: () => setSidebarPanel('explorer'),
      category: 'View'
    },
    { 
      id: 'show-search', 
      label: 'Show Search', 
      shortcut: 'Ctrl+Shift+F', 
      action: () => setSidebarPanel('search'),
      category: 'View'
    },
    { 
      id: 'show-git', 
      label: 'Show Source Control', 
      shortcut: 'Ctrl+Shift+G', 
      action: () => setSidebarPanel('git'),
      category: 'View'
    },
    { 
      id: 'show-extensions', 
      label: 'Show Extensions', 
      shortcut: 'Ctrl+Shift+X', 
      action: () => setSidebarPanel('extensions'),
      category: 'View'
    },
    {
      id: 'new-file',
      label: 'New File',
      shortcut: 'Ctrl+N',
      action: () => {
        setNewFileDialog(true);
      },
      category: 'File'
    },
    {
      id: 'save',
      label: 'Save',
      shortcut: 'Ctrl+S',
      action: () => {
        if (activeTabId) {
          saveTab(activeTabId);
          toast({
            title: 'File saved',
            description: 'File saved successfully',
          });
        }
      },
      category: 'File'
    },
    {
      id: 'find',
      label: 'Find',
      shortcut: 'Ctrl+F',
      action: () => {
        toggleFindReplace();
      },
      category: 'Edit'
    },
  ], [toggleSidebar, toggleTerminal, togglePreview, setSidebarPanel, createFile, activeTabId, saveTab, toggleFindReplace]);

  // Flatten files for quick open
  const flattenFiles = (nodes: typeof files): typeof files => {
    let result: typeof files = [];
    for (const node of nodes) {
      if (node.type === 'file') {
        result.push(node);
      }
      if (node.children) {
        result = [...result, ...flattenFiles(node.children)];
      }
    }
    return result;
  };

  const allFiles = useMemo(() => flattenFiles(files), [files]);

  const filteredItems = useMemo(() => {
    const query = search.toLowerCase();
    if (!query) {
      return { commands: commands.slice(0, 6), files: [] };
    }

    const matchedCommands = commands.filter(c => 
      c.label.toLowerCase().includes(query)
    );

    const matchedFiles = allFiles.filter(f => 
      f.name.toLowerCase().includes(query) || 
      f.path.toLowerCase().includes(query)
    );

    return { commands: matchedCommands, files: matchedFiles.slice(0, 5) };
  }, [search, commands, allFiles]);

  const allItems = [...filteredItems.commands, ...filteredItems.files];

  useEffect(() => {
    setSelectedIndex(0);
  }, [search]);

  useEffect(() => {
    if (!commandPaletteOpen) {
      setSearch('');
      setSelectedIndex(0);
    }
  }, [commandPaletteOpen]);

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (!commandPaletteOpen) return;

      if (e.key === 'Escape') {
        toggleCommandPalette();
      } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        setSelectedIndex(i => Math.min(i + 1, allItems.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setSelectedIndex(i => Math.max(i - 1, 0));
      } else if (e.key === 'Enter') {
        e.preventDefault();
        const item = allItems[selectedIndex];
        if (item) {
          if ('action' in item) {
            item.action();
          } else {
            openFile(item);
          }
          toggleCommandPalette();
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [commandPaletteOpen, selectedIndex, allItems, toggleCommandPalette, openFile]);

  if (!commandPaletteOpen) return null;

  const getIcon = (item: Command | typeof allFiles[0]) => {
    if ('action' in item) {
      switch (item.id) {
        case 'toggle-terminal': return <Terminal className="w-4 h-4" />;
        case 'show-git': return <GitBranch className="w-4 h-4" />;
        case 'show-explorer': return <FolderOpen className="w-4 h-4" />;
        default: return <Settings className="w-4 h-4" />;
      }
    }
    return <FileCode2 className="w-4 h-4" />;
  };

  return (
    <div className="command-palette" onClick={toggleCommandPalette}>
      <div 
        className="command-palette-content animate-slide-up"
        onClick={e => e.stopPropagation()}
      >
        <div className="flex items-center gap-2 px-3 py-2 border-b border-border">
          <Search className="w-4 h-4 text-muted-foreground" />
          <input
            type="text"
            value={search}
            onChange={e => setSearch(e.target.value)}
            placeholder="Search commands or files..."
            className="flex-1 bg-transparent outline-none text-xs text-foreground placeholder:text-muted-foreground"
            autoFocus
          />
        </div>

        <div className="max-h-72 overflow-auto scrollbar-thin">
          {filteredItems.commands.length > 0 && (
            <div className="py-1.5">
              <div className="px-3 py-1 text-[10px] text-muted-foreground uppercase">Commands</div>
              {filteredItems.commands.map((cmd, index) => (
                <div
                  key={cmd.id}
                  onClick={() => {
                    cmd.action();
                    toggleCommandPalette();
                  }}
                  className={`flex items-center justify-between px-3 py-1.5 cursor-pointer transition-colors ${
                    selectedIndex === index ? 'bg-accent' : 'hover:bg-muted'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    {getIcon(cmd)}
                    <span className="text-xs">{cmd.label}</span>
                  </div>
                  {cmd.shortcut && (
                    <kbd className="text-[10px] px-1 py-0.5 rounded bg-muted text-muted-foreground">
                      {cmd.shortcut}
                    </kbd>
                  )}
                </div>
              ))}
            </div>
          )}

          {filteredItems.files.length > 0 && (
            <div className="py-1.5 border-t border-border">
              <div className="px-3 py-1 text-[10px] text-muted-foreground uppercase">Files</div>
              {filteredItems.files.map((file, index) => (
                <div
                  key={file.id}
                  onClick={() => {
                    openFile(file);
                    toggleCommandPalette();
                  }}
                  className={`flex items-center gap-2 px-3 py-1.5 cursor-pointer transition-colors ${
                    selectedIndex === filteredItems.commands.length + index 
                      ? 'bg-accent' 
                      : 'hover:bg-muted'
                  }`}
                >
                  <FileCode2 className="w-3.5 h-3.5 text-muted-foreground" />
                  <div>
                    <div className="text-xs">{file.name}</div>
                    <div className="text-[10px] text-muted-foreground">{file.path}</div>
                  </div>
                </div>
              ))}
            </div>
          )}

          {allItems.length === 0 && (
            <div className="px-3 py-6 text-center text-xs text-muted-foreground">
              No results found
            </div>
          )}
        </div>
      </div>

      <NewFileDialog
        open={newFileDialog}
        onOpenChange={setNewFileDialog}
        onConfirm={(name) => {
          createFile('/', name);
          toast({
            title: 'File created',
            description: `${name} created successfully`,
          });
        }}
        type="file"
      />
    </div>
  );
};
