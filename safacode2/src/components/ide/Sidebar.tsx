import { useState } from 'react';
import { 
  Files, 
  Search, 
  GitBranch, 
  Puzzle, 
  Settings,
  Play,
  Bug,
  Layers
} from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import { FileExplorer } from './FileExplorer';
import { SearchPanel } from './SearchPanel';
import { GitPanel } from './GitPanel';
import { ExtensionsPanel } from './ExtensionsPanel';
import { SettingsDialog } from './SettingsDialog';
import type { PanelType } from '@/types/ide';

const sidebarItems: { id: PanelType; icon: React.ReactNode; label: string }[] = [
  { id: 'explorer', icon: <Files className="w-4 h-4" />, label: 'Explorer' },
  { id: 'search', icon: <Search className="w-4 h-4" />, label: 'Search' },
  { id: 'git', icon: <GitBranch className="w-4 h-4" />, label: 'Source Control' },
  { id: 'extensions', icon: <Puzzle className="w-4 h-4" />, label: 'Extensions' },
];

export const Sidebar: React.FC = () => {
  const { sidebarOpen, activeSidebarPanel, setSidebarPanel } = useIDEStore();
  const [settingsOpen, setSettingsOpen] = useState(false);

  const renderPanel = () => {
    switch (activeSidebarPanel) {
      case 'explorer':
        return <FileExplorer />;
      case 'search':
        return <SearchPanel />;
      case 'git':
        return <GitPanel />;
      case 'extensions':
        return <ExtensionsPanel />;
      default:
        return <FileExplorer />;
    }
  };

  return (
    <div className="flex h-full">
      {/* Activity bar */}
      <div className="w-9 flex flex-col items-center py-1 gap-0.5" style={{ backgroundColor: 'hsl(var(--sidebar))', borderRight: '1px solid hsl(var(--panel-border))' }}>
        {sidebarItems.map(({ id, icon, label }) => (
          <button
            key={id}
            onClick={() => setSidebarPanel(id)}
            className={`w-8 h-8 flex items-center justify-center transition-all relative group ${
              activeSidebarPanel === id && sidebarOpen
                ? 'text-foreground bg-sidebar-hover'
                : 'text-muted-foreground hover:text-foreground hover:bg-sidebar-hover'
            }`}
            title={label}
          >
            {activeSidebarPanel === id && sidebarOpen && (
              <span className="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-6 bg-primary rounded-r transition-all" />
            )}
            <span className="relative z-10 transition-transform group-hover:scale-110">
              {icon}
            </span>
          </button>
        ))}
        
        <div className="flex-1" />
        
        <button
          onClick={() => setSettingsOpen(true)}
          className="w-8 h-8 flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-sidebar-hover transition-colors"
          title="Settings"
        >
          <Settings className="w-4 h-4" />
        </button>
      </div>

      {/* Panel content */}
      {sidebarOpen && (
        <div className="w-56 border-r border-panel-border" style={{ backgroundColor: 'hsl(var(--sidebar))', borderRightColor: 'hsl(var(--panel-border))' }}>
          {renderPanel()}
        </div>
      )}

      {/* Settings Dialog */}
      <SettingsDialog open={settingsOpen} onOpenChange={setSettingsOpen} />
    </div>
  );
};
