import { X } from 'lucide-react';
import { FileIcon } from './FileIcon';
import { useIDEStore } from '@/stores/ideStore';

export const EditorTabs: React.FC = () => {
  const { tabs, activeTabId, setActiveTab, closeTab } = useIDEStore();

  if (tabs.length === 0) {
    return null;
  }

  return (
    <div className="flex items-center border-b overflow-x-auto scrollbar-thin" style={{ backgroundColor: 'hsl(var(--tab))', borderBottomColor: 'hsl(var(--panel-border))' }}>
      {tabs.map((tab) => (
        <div
          key={tab.id}
          className={`ide-tab group ${tab.id === activeTabId ? 'active' : ''} ${tab.isDirty ? 'dirty' : ''}`}
          onClick={() => setActiveTab(tab.id)}
        >
          <FileIcon file={{ id: tab.id, name: tab.name, type: 'file', path: tab.path }} />
          <span className="truncate max-w-32 text-xs">{tab.name}</span>
          <button
            onClick={(e) => {
              e.stopPropagation();
              closeTab(tab.id);
            }}
            className="p-0.5 hover:bg-muted transition-colors ml-0.5 opacity-0 group-hover:opacity-100"
          >
            <X className="w-3 h-3" />
          </button>
        </div>
      ))}
    </div>
  );
};
