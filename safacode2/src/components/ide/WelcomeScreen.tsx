import { FolderOpen, Clock, Plus, GitBranch } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import { useState, useEffect } from 'react';
import { useFilePicker } from './FilePicker';
import { useToast } from '@/components/ui/use-toast';
import { CloneRepositoryDialog } from './CloneRepositoryDialog';
import { KeyboardShortcutsDialog } from './KeyboardShortcutsDialog';
import { SettingsDialog } from './SettingsDialog';

interface RecentProject {
  name: string;
  path: string;
  lastOpened: Date;
}

const quickActions = [
  { icon: Plus, label: 'New File', shortcut: 'Ctrl+N' },
  { icon: FolderOpen, label: 'Open Folder', shortcut: 'Ctrl+K Ctrl+O' },
  { icon: GitBranch, label: 'Clone Repository', shortcut: '' },
];

export const WelcomeScreen: React.FC = () => {
  const { setShowWelcome, openFile, files, setFiles } = useIDEStore();
  const { openDirectoryPicker } = useFilePicker();
  const { toast } = useToast();
  const [recentProjects, setRecentProjects] = useState<RecentProject[]>([]);
  const [cloneDialogOpen, setCloneDialogOpen] = useState(false);
  const [shortcutsDialogOpen, setShortcutsDialogOpen] = useState(false);
  const [settingsDialogOpen, setSettingsDialogOpen] = useState(false);

  // Load recent projects from localStorage
  useEffect(() => {
    const stored = localStorage.getItem('safecode-recent-projects');
    if (stored) {
      try {
        const projects = JSON.parse(stored).map((p: any) => ({
          ...p,
          lastOpened: new Date(p.lastOpened)
        }));
        setRecentProjects(projects);
      } catch (e) {
        console.error('Error loading recent projects:', e);
      }
    }
  }, []);

  const saveRecentProject = (name: string, path: string) => {
    const project: RecentProject = {
      name,
      path,
      lastOpened: new Date()
    };
    const updated = [project, ...recentProjects.filter(p => p.path !== path)].slice(0, 5);
    setRecentProjects(updated);
    localStorage.setItem('safecode-recent-projects', JSON.stringify(updated));
  };

  const handleOpenFolder = async () => {
    try {
      const fileList = await openDirectoryPicker();
      if (fileList && fileList.length > 0) {
        const files = Array.from(fileList);
        const fileTree = await buildFileTree(files);
        setFiles(fileTree);
        
        const folderName = files[0]?.webkitRelativePath?.split('/')[0] || 'Untitled';
        saveRecentProject(folderName, folderName);
        
        toast({
          title: 'Folder opened',
          description: `${files.length} files loaded`,
        });
        setShowWelcome(false);
      }
    } catch (error) {
      console.error('Error opening folder:', error);
      toast({
        title: 'Error',
        description: 'Failed to open folder',
        variant: 'destructive',
      });
    }
  };

  const buildFileTree = async (files: File[]): Promise<import('@/types/ide').FileNode[]> => {
    const folderMap = new Map<string, import('@/types/ide').FileNode>();
    const rootNodes: import('@/types/ide').FileNode[] = [];
    
    // Process all files
    const filePromises = files.map(async (file) => {
      const relativePath = file.webkitRelativePath || file.name;
      const parts = relativePath.split('/').filter(p => p);
      
      if (parts.length === 0) return;
      
      // Build folder structure
      let currentPath = '';
      for (let i = 0; i < parts.length - 1; i++) {
        const folderName = parts[i];
        const folderPath = currentPath ? `${currentPath}/${folderName}` : `/${folderName}`;
        
        if (!folderMap.has(folderPath)) {
          const folderNode: import('@/types/ide').FileNode = {
            id: `folder-${Date.now()}-${Math.random()}`,
            name: folderName,
            type: 'folder',
            path: folderPath,
            isExpanded: false,
            children: []
          };
          folderMap.set(folderPath, folderNode);
          
          // Add to parent or root
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
      
      // Read file content
      const content = await new Promise<string>((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target?.result as string || '');
        reader.onerror = () => resolve('');
        reader.readAsText(file);
      });
      
      // Create file node
      const fileName = parts[parts.length - 1];
      const filePath = currentPath ? `${currentPath}/${fileName}` : `/${fileName}`;
      const fileNode: import('@/types/ide').FileNode = {
        id: `file-${Date.now()}-${Math.random()}`,
        name: fileName,
        type: 'file',
        path: filePath,
        content,
        language: getLanguageFromFileName(fileName)
      };
      
      // Add file to parent folder or root
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

  const handleGetStarted = () => {
    const findFirstFile = (nodes: typeof files): typeof files[0] | null => {
      for (const node of nodes) {
        if (node.type === 'file') return node;
        if (node.children) {
          const found = findFirstFile(node.children);
          if (found) return found;
        }
      }
      return null;
    };

    const firstFile = findFirstFile(files);
    if (firstFile) {
      openFile(firstFile);
    }
    setShowWelcome(false);
  };

  const formatLastOpened = (date: Date): string => {
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const days = Math.floor(hours / 24);
    
    if (days > 0) return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    if (hours > 0) return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    return 'Just now';
  };

  const handleCloneRepository = async (url: string, destination: string) => {
    try {
      // Em um ambiente real, isso seria uma chamada para uma API backend ou Electron
      // Por enquanto, vamos simular o processo de clone
      
      toast({
        title: 'Cloning repository...',
        description: `Cloning ${url}`,
      });

      // Simular delay de clone
      await new Promise(resolve => setTimeout(resolve, 2000));

      // Em um ambiente real, aqui você faria:
      // 1. Chamar API backend para clonar o repositório
      // 2. Ou usar Electron para executar `git clone`
      // 3. Processar os arquivos clonados e adicionar ao file tree

      // Por enquanto, vamos criar uma estrutura simulada
      const repoName = destination || extractRepoName(url);
      
      // Criar estrutura básica de um repositório clonado
      const clonedFiles: typeof files = [
        {
          id: `folder-${Date.now()}`,
          name: repoName,
          type: 'folder',
          path: `/${repoName}`,
          isExpanded: true,
          children: [
            {
              id: `file-${Date.now()}-1`,
              name: 'README.md',
              type: 'file',
              path: `/${repoName}/README.md`,
              content: `# ${repoName}\n\nCloned from ${url}\n\n## Getting Started\n\nThis repository was cloned using SAFECODE IDE.`,
              language: 'markdown'
            },
            {
              id: `file-${Date.now()}-2`,
              name: '.git',
              type: 'folder',
              path: `/${repoName}/.git`,
              isExpanded: false,
              children: []
            }
          ]
        }
      ];

      setFiles(clonedFiles);
      saveRecentProject(repoName, repoName);

      toast({
        title: 'Repository cloned',
        description: `${repoName} cloned successfully`,
      });

      setShowWelcome(false);
    } catch (error) {
      throw new Error(error instanceof Error ? error.message : 'Failed to clone repository');
    }
  };

  const extractRepoName = (url: string): string => {
    try {
      const match = url.match(/\/([^\/]+?)(?:\.git)?$/);
      return match ? match[1] : 'repository';
    } catch {
      return 'repository';
    }
  };

  return (
    <div className="flex-1 flex items-center justify-center p-6" style={{ backgroundColor: 'hsl(var(--editor))' }}>
      <div className="max-w-xl w-full animate-fade-in">
        {/* Logo */}
        <div className="text-center mb-10">
          <div className="inline-flex items-center justify-center mb-5">
            <img 
              src="/logos (6).png" 
              alt="SAFECODE IDE" 
              className="w-16 h-16 object-contain"
              onError={(e) => {
                e.currentTarget.style.display = 'none';
              }}
            />
          </div>
          <h1 className="text-3xl font-semibold mb-2 text-foreground">
            SAFECODE IDE
          </h1>
          <p className="text-muted-foreground text-sm">
            Modern code editor for developers
          </p>
        </div>

        <div className="grid md:grid-cols-2 gap-4">
          {/* Quick Actions */}
          <div>
            <h2 className="text-[10px] font-medium text-muted-foreground uppercase tracking-wider mb-2.5">
              Start
            </h2>
            <div className="space-y-1.5">
              {quickActions.map(({ icon: Icon, label, shortcut }) => (
                <button
                  key={label}
                  className="welcome-card w-full flex items-center justify-between group"
                  onClick={() => {
                    if (label === 'Open Folder') {
                      handleOpenFolder();
                    } else if (label === 'New File') {
                      handleGetStarted();
                    } else if (label === 'Clone Repository') {
                      setCloneDialogOpen(true);
                    }
                  }}
                >
                  <div className="flex items-center gap-3">
                    <Icon className="w-4 h-4 text-primary" />
                    <span className="text-sm">{label}</span>
                  </div>
                  {shortcut && (
                    <kbd className="text-xs px-2 py-1 bg-muted text-muted-foreground group-hover:bg-primary/10 transition-colors">
                      {shortcut}
                    </kbd>
                  )}
                </button>
              ))}
            </div>
          </div>

          {/* Recent Projects */}
          <div>
            <h2 className="text-[10px] font-medium text-muted-foreground uppercase tracking-wider mb-2.5">
              Recent
            </h2>
            <div className="space-y-1.5">
              {recentProjects.length > 0 ? (
                recentProjects.map((project) => (
                <button
                    key={project.path}
                  className="welcome-card w-full text-left group"
                    onClick={async () => {
                      // Reopen folder
                      await handleOpenFolder();
                    }}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <FolderOpen className="w-4 h-4 text-primary" />
                      <div>
                        <div className="font-medium text-sm">{project.name}</div>
                        <div className="text-xs text-muted-foreground truncate max-w-48">
                          {project.path}
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      <Clock className="w-3 h-3" />
                        {formatLastOpened(project.lastOpened)}
                      </div>
                    </div>
                  </button>
                ))
              ) : (
                <div className="text-center py-4 text-muted-foreground text-sm">
                  No recent projects
                  </div>
              )}
            </div>
          </div>
        </div>

        {/* Footer links */}
        <div className="mt-10 pt-4 border-t border-panel-border flex justify-center gap-4">
          <button
            onClick={() => window.open('/lp/docs.html', '_blank')}
            className="text-[10px] text-muted-foreground hover:text-foreground transition-colors"
          >
            Documentation
          </button>
          <button
            onClick={() => setShortcutsDialogOpen(true)}
            className="text-[10px] text-muted-foreground hover:text-foreground transition-colors"
          >
            Keyboard Shortcuts
          </button>
            <button
            onClick={() => setSettingsDialogOpen(true)}
            className="text-[10px] text-muted-foreground hover:text-foreground transition-colors"
            >
            Settings
            </button>
        </div>

        {/* Version */}
        <div className="mt-3 text-center text-[10px] text-muted-foreground">
          SAFECODE IDE v1.0.0
        </div>
      </div>

      {/* Clone Repository Dialog */}
      <CloneRepositoryDialog
        open={cloneDialogOpen}
        onOpenChange={setCloneDialogOpen}
        onConfirm={handleCloneRepository}
      />
      
      {/* Keyboard Shortcuts Dialog */}
      <KeyboardShortcutsDialog
        open={shortcutsDialogOpen}
        onOpenChange={setShortcutsDialogOpen}
      />
      
      {/* Settings Dialog */}
      <SettingsDialog
        open={settingsDialogOpen}
        onOpenChange={setSettingsDialogOpen}
      />
    </div>
  );
};
