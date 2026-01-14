import { GitBranch, AlertCircle, Bell, Check } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';

export const StatusBar: React.FC = () => {
  const { tabs, activeTabId, gitStatus } = useIDEStore();
  
  const activeTab = tabs.find(t => t.id === activeTabId);

  return (
    <div className="status-bar">
      <div className="flex items-center gap-2">
        {/* Logo */}
        <div className="flex items-center gap-1 mr-1">
          <img 
            src="/logos (6).png" 
            alt="SAFECODE" 
            className="w-3 h-3 object-contain opacity-70"
            onError={(e) => {
              e.currentTarget.style.display = 'none';
            }}
          />
        </div>
        
        {/* Git branch */}
        {gitStatus.branch && (
          <div className="flex items-center gap-1 hover:bg-muted px-1.5 py-0.5 cursor-pointer transition-all rounded group">
            <GitBranch className="w-3 h-3 transition-transform group-hover:rotate-12" />
            <span className="text-[10px] font-medium">{gitStatus.branch}</span>
            {gitStatus.modified.length > 0 && (
              <span className="text-[10px] text-warning font-bold animate-pulse">●</span>
            )}
          </div>
        )}

        {/* Problems */}
        <div className="flex items-center gap-1 hover:bg-muted px-1.5 py-0.5 cursor-pointer transition-all rounded group">
          <AlertCircle className="w-3 h-3 transition-transform group-hover:scale-110" />
          <span className="text-[10px]">0</span>
          <span className="mx-0.5 text-[10px]">⚠</span>
          <span className="text-[10px]">0</span>
        </div>
      </div>

      <div className="flex items-center gap-2">
        {/* Cursor position */}
        {activeTab && (
          <span className="hover:bg-muted px-1.5 py-0.5 cursor-pointer transition-colors text-[10px] rounded">
            Ln {activeTab.cursorPosition.line}, Col {activeTab.cursorPosition.column}
          </span>
        )}

        {/* Language */}
        {activeTab && (
          <span className="hover:bg-muted px-1.5 py-0.5 cursor-pointer transition-colors capitalize text-[10px] rounded">
            {activeTab.language}
          </span>
        )}

        {/* Encoding */}
        <span className="hover:bg-muted px-1.5 py-0.5 cursor-pointer transition-colors text-[10px] rounded">
          UTF-8
        </span>

        {/* Notifications */}
        <button className="flex items-center gap-0.5 hover:bg-muted px-1.5 py-0.5 transition-colors rounded">
          <Bell className="w-3 h-3" />
        </button>

        {/* Sync status */}
        <div className="flex items-center gap-0.5 text-success px-1.5">
          <Check className="w-3 h-3" />
        </div>
      </div>
    </div>
  );
};
