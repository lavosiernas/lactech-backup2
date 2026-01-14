import { useState } from 'react';
import { GitBranch, AlertCircle, Bell, Check } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import { ProblemsPanel } from './ProblemsPanel';
import { NotificationsPanel } from './NotificationsPanel';

export const StatusBar: React.FC = () => {
  const { 
    tabs, 
    activeTabId, 
    gitStatus,
    problems,
    notifications,
    markNotificationAsRead,
    markAllNotificationsAsRead,
    clearNotifications
  } = useIDEStore();
  
  const [problemsOpen, setProblemsOpen] = useState(false);
  const [notificationsOpen, setNotificationsOpen] = useState(false);
  
  const activeTab = tabs.find(t => t.id === activeTabId);
  
  const errors = problems.filter(p => p.type === 'error').length;
  const warnings = problems.filter(p => p.type === 'warning').length;
  const unreadNotifications = notifications.filter(n => !n.read).length;

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
        <button
          onClick={() => setProblemsOpen(true)}
          className="flex items-center gap-1 hover:bg-muted px-1.5 py-0.5 cursor-pointer transition-all rounded group"
        >
          <AlertCircle className="w-3 h-3 transition-transform group-hover:scale-110" />
          <span className="text-[10px]">{errors}</span>
          <span className="mx-0.5 text-[10px]">⚠</span>
          <span className="text-[10px]">{warnings}</span>
        </button>
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
        <button
          onClick={() => setNotificationsOpen(true)}
          className="flex items-center gap-0.5 hover:bg-muted px-1.5 py-0.5 transition-colors rounded relative"
        >
          <Bell className="w-3 h-3" />
          {unreadNotifications > 0 && (
            <span className="absolute -top-0.5 -right-0.5 w-2 h-2 bg-primary rounded-full" />
          )}
        </button>

        {/* Sync status */}
        <div className="flex items-center gap-0.5 text-success px-1.5">
          <Check className="w-3 h-3" />
        </div>
      </div>

      {/* Problems Panel */}
      <ProblemsPanel
        open={problemsOpen}
        onOpenChange={setProblemsOpen}
        problems={problems}
      />

      {/* Notifications Panel */}
      <NotificationsPanel
        open={notificationsOpen}
        onOpenChange={setNotificationsOpen}
        notifications={notifications}
        onMarkAsRead={markNotificationAsRead}
        onMarkAllAsRead={markAllNotificationsAsRead}
        onClear={clearNotifications}
      />
    </div>
  );
};
