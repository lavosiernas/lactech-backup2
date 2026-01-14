import React, { useState } from 'react';
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
    <div className="flex h-full" style={{ backgroundColor: '#000000' }}>
      {/* Activity bar - estilo Cursor */}
      <div 
        className="flex flex-col items-center py-1 gap-0.5" 
        style={{ 
          width: '48px',
          backgroundColor: '#000000', 
          borderRight: '1px solid hsl(var(--panel-border))' 
        }}
      >
        {sidebarItems.map(({ id, icon, label }) => (
          <button
            key={id}
            onClick={() => setSidebarPanel(id)}
            className="w-10 h-10 flex items-center justify-center transition-all relative group rounded"
            style={{
              color: activeSidebarPanel === id && sidebarOpen 
                ? '#ffffff' 
                : 'rgba(255, 255, 255, 0.5)',
              backgroundColor: activeSidebarPanel === id && sidebarOpen
                ? 'rgba(59, 130, 246, 0.15)'
                : 'transparent'
            }}
            onMouseEnter={(e) => {
              if (!(activeSidebarPanel === id && sidebarOpen)) {
                e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
                e.currentTarget.style.color = 'rgba(255, 255, 255, 0.8)';
              }
            }}
            onMouseLeave={(e) => {
              if (!(activeSidebarPanel === id && sidebarOpen)) {
                e.currentTarget.style.backgroundColor = 'transparent';
                e.currentTarget.style.color = 'rgba(255, 255, 255, 0.5)';
              }
            }}
            title={label}
          >
            {activeSidebarPanel === id && sidebarOpen && (
              <span 
                className="absolute left-0 top-1/2 -translate-y-1/2 w-0.5 h-8 rounded-r transition-all" 
                style={{ backgroundColor: '#3b82f6' }}
              />
            )}
            <span className="relative z-10">
              {icon}
            </span>
          </button>
        ))}
        
        <div className="flex-1" />
        
        <button
          onClick={() => setSettingsOpen(true)}
          className="w-10 h-10 flex items-center justify-center rounded transition-all"
          style={{
            color: 'rgba(255, 255, 255, 0.5)',
            backgroundColor: 'transparent'
          }}
          onMouseEnter={(e) => {
            e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
            e.currentTarget.style.color = 'rgba(255, 255, 255, 0.8)';
          }}
          onMouseLeave={(e) => {
            e.currentTarget.style.backgroundColor = 'transparent';
            e.currentTarget.style.color = 'rgba(255, 255, 255, 0.5)';
          }}
          title="Settings"
        >
          <Settings className="w-4 h-4" />
        </button>
      </div>

      {/* Panel content - estilo Cursor */}
      {sidebarOpen && (
        <div 
          style={{ 
            width: '280px',
            backgroundColor: '#000000', 
            display: 'flex',
            flexDirection: 'column',
            height: '100%'
          }}
        >
          {renderPanel()}
        </div>
      )}

      {/* Settings Dialog */}
      <SettingsDialog open={settingsOpen} onOpenChange={setSettingsOpen} />
    </div>
  );
};
