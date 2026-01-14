import { useState } from 'react';
import { Puzzle, Download, Star, Check, X } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { ConfirmDialog } from './ConfirmDialog';

interface Extension {
  id: string;
  name: string;
  author: string;
  description: string;
  installed: boolean;
  downloads: string;
}

const initialExtensions: Extension[] = [
  { 
    id: '1', 
    name: 'Prettier', 
    author: 'Prettier', 
    description: 'Code formatter using prettier', 
    installed: true,
    downloads: '45M'
  },
  { 
    id: '2', 
    name: 'ESLint', 
    author: 'Microsoft', 
    description: 'Integrates ESLint into SAFECODE', 
    installed: true,
    downloads: '32M'
  },
  { 
    id: '3', 
    name: 'GitLens', 
    author: 'GitKraken', 
    description: 'Git supercharged', 
    installed: false,
    downloads: '28M'
  },
  { 
    id: '4', 
    name: 'Auto Rename Tag', 
    author: 'Jun Han', 
    description: 'Auto rename paired HTML/XML tag', 
    installed: false,
    downloads: '18M'
  },
  { 
    id: '5', 
    name: 'Material Icon Theme', 
    author: 'Philipp Kief', 
    description: 'Material Design Icons for VS Code', 
    installed: false,
    downloads: '22M'
  },
];

export const ExtensionsPanel: React.FC = () => {
  const [extensions, setExtensions] = useState<Extension[]>(initialExtensions);
  const [searchQuery, setSearchQuery] = useState('');
  const { toast } = useToast();
  const [uninstallConfirm, setUninstallConfirm] = useState<{ open: boolean; id: string; name: string }>({ open: false, id: '', name: '' });

  const filteredExtensions = extensions.filter(ext =>
    ext.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
    ext.description.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const handleInstall = (id: string) => {
    const ext = extensions.find(e => e.id === id);
    setExtensions(extensions.map(ext =>
      ext.id === id ? { ...ext, installed: true } : ext
    ));
    toast({
      title: 'Extension installed',
      description: `${ext?.name} installed successfully`,
    });
  };

  const handleUninstall = (id: string) => {
    const ext = extensions.find(e => e.id === id);
    if (ext) {
      setUninstallConfirm({ open: true, id, name: ext.name });
    }
  };

  const confirmUninstall = () => {
    setExtensions(extensions.map(ext =>
      ext.id === uninstallConfirm.id ? { ...ext, installed: false } : ext
    ));
    toast({
      title: 'Extension uninstalled',
      description: `${uninstallConfirm.name} uninstalled successfully`,
    });
  };

  return (
    <div className="h-full flex flex-col">
      <div className="flex items-center justify-between px-3 py-2 border-b border-panel-border">
        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Extensions
        </span>
      </div>
      
      <div className="p-2">
        <input
          type="text"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          placeholder="Search extensions..."
          className="w-full px-2 py-1.5 text-sm bg-input border border-border rounded focus:outline-none focus:border-primary"
        />
      </div>

      <div className="flex-1 overflow-auto scrollbar-thin">
        {/* Installed */}
        {filteredExtensions.filter(e => e.installed).length > 0 && (
          <>
            <div className="px-2 py-1 text-xs text-muted-foreground uppercase">
              Installed
            </div>
            {filteredExtensions.filter(e => e.installed).map((ext) => (
              <div key={ext.id} className="px-2 py-2 hover:bg-sidebar-hover group">
                <div className="flex items-start gap-2">
                  <div className="w-10 h-10 rounded bg-gradient-to-br from-primary to-purple-500 flex items-center justify-center flex-shrink-0">
                    <Puzzle className="w-5 h-5 text-white" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-sm truncate">{ext.name}</span>
                      <Check className="w-3.5 h-3.5 text-success" />
                    </div>
                    <div className="text-xs text-muted-foreground">{ext.author}</div>
                    <div className="text-xs text-muted-foreground truncate mt-0.5">
                      {ext.description}
                    </div>
                  </div>
                  <button
                    onClick={() => handleUninstall(ext.id)}
                    className="opacity-0 group-hover:opacity-100 p-1 hover:bg-muted rounded transition-opacity"
                    title="Uninstall"
                  >
                    <X className="w-3.5 h-3.5" />
                  </button>
                </div>
              </div>
            ))}
          </>
        )}

        {/* Recommended */}
        {filteredExtensions.filter(e => !e.installed).length > 0 && (
          <>
            <div className="px-2 py-1 mt-2 text-xs text-muted-foreground uppercase border-t border-panel-border pt-3">
              Recommended
            </div>
            {filteredExtensions.filter(e => !e.installed).map((ext) => (
              <div key={ext.id} className="px-2 py-2 hover:bg-sidebar-hover group">
                <div className="flex items-start gap-2">
                  <div className="w-10 h-10 rounded bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center flex-shrink-0">
                    <Puzzle className="w-5 h-5 text-white" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-medium text-sm truncate">{ext.name}</span>
                    </div>
                    <div className="text-xs text-muted-foreground">{ext.author}</div>
                    <div className="text-xs text-muted-foreground truncate mt-0.5">
                      {ext.description}
                    </div>
                    <div className="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                      <Download className="w-3 h-3" />
                      <span>{ext.downloads}</span>
                      <Star className="w-3 h-3 ml-2 text-warning" />
                      <span>5.0</span>
                    </div>
                  </div>
                  <button
                    onClick={() => handleInstall(ext.id)}
                    className="opacity-0 group-hover:opacity-100 px-2 py-1 text-xs bg-primary text-primary-foreground rounded hover:opacity-90 transition-opacity"
                    title="Install"
                  >
                    Install
                  </button>
                </div>
              </div>
            ))}
          </>
        )}

        {filteredExtensions.length === 0 && (
          <div className="px-2 py-8 text-center text-sm text-muted-foreground">
            No extensions found
          </div>
        )}
      </div>

      <ConfirmDialog
        open={uninstallConfirm.open}
        onOpenChange={(open) => setUninstallConfirm({ ...uninstallConfirm, open })}
        onConfirm={confirmUninstall}
        title="Uninstall Extension"
        message={`Are you sure you want to uninstall "${uninstallConfirm.name}"?`}
        confirmText="Uninstall"
        variant="destructive"
      />
    </div>
  );
};
