import React, { useState } from 'react';
import { GitCommit, GitBranch, Clock, User, MessageSquare } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';

interface TimelineEvent {
  id: string;
  type: 'commit' | 'branch' | 'tag';
  message: string;
  author: string;
  date: Date;
  hash?: string;
  branch?: string;
}

export const TimelinePanel: React.FC = () => {
  const { files } = useIDEStore();
  const [selectedBranch, setSelectedBranch] = useState<string>('main');

  // Em um ambiente real, isso viria do Git
  const getTimelineEvents = (): TimelineEvent[] => {
    // Retornar array vazio - sem dados simulados
    return [];
  };

  const timelineEvents = getTimelineEvents();

  const formatDate = (date: Date): string => {
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor(diff / (1000 * 60));

    if (days > 0) return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    if (hours > 0) return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    if (minutes > 0) return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`;
    return 'Just now';
  };

  const getEventIcon = (type: TimelineEvent['type']) => {
    switch (type) {
      case 'commit':
        return <GitCommit className="w-3 h-3" />;
      case 'branch':
        return <GitBranch className="w-3 h-3" />;
      default:
        return <GitCommit className="w-3 h-3" />;
    }
  };

  return (
    <div className="h-full flex flex-col" style={{ backgroundColor: '#000000' }}>
      <div 
        className="flex items-center justify-between px-3 py-2" 
        style={{ 
          backgroundColor: '#000000'
        }}
      >
        <select
          value={selectedBranch}
          onChange={(e) => setSelectedBranch(e.target.value)}
          className="text-[10px] rounded px-2 py-1 cursor-pointer focus:outline-none"
          style={{ 
            backgroundColor: 'rgba(255, 255, 255, 0.08)',
            border: '1px solid hsl(var(--panel-border))',
            color: 'rgba(255, 255, 255, 0.7)'
          }}
        >
          <option value="main" style={{ backgroundColor: '#000000' }}>main</option>
          <option value="develop" style={{ backgroundColor: '#000000' }}>develop</option>
        </select>
      </div>
      <div className="flex-1 overflow-auto hide-scrollbar py-2">
        {timelineEvents.length > 0 ? (
          <div className="px-2 space-y-1">
            {timelineEvents.map((event, index) => (
              <div
                key={event.id}
                className="relative pl-4 pb-3 group cursor-pointer hover:bg-sidebar-hover rounded transition-colors"
              >
                {/* Timeline line */}
                {index < timelineEvents.length - 1 && (
                  <div
                    className="absolute left-[5px] top-4 bottom-0 w-px"
                    style={{ backgroundColor: 'hsl(var(--panel-border))' }}
                  />
                )}
                
                {/* Timeline dot */}
                <div
                  className="absolute left-0 top-1 w-2 h-2 rounded-full border-2"
                  style={{
                    backgroundColor: event.type === 'commit' ? 'hsl(var(--primary))' : 'hsl(var(--muted-foreground))',
                    borderColor: 'hsl(var(--sidebar))',
                    zIndex: 1
                  }}
                />
                
                {/* Event content */}
                <div className="flex items-start gap-2">
                  <div className="text-muted-foreground mt-0.5 flex-shrink-0">
                    {getEventIcon(event.type)}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-0.5">
                      <span className="text-xs font-medium text-foreground truncate">
                        {event.message}
                      </span>
                      {event.hash && (
                        <span className="text-[10px] text-muted-foreground font-mono flex-shrink-0">
                          {event.hash.substring(0, 7)}
                        </span>
                      )}
                    </div>
                    <div className="flex items-center gap-3 text-[10px] text-muted-foreground">
                      <div className="flex items-center gap-1">
                        <User className="w-3 h-3" />
                        <span>{event.author}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <Clock className="w-3 h-3" />
                        <span>{formatDate(event.date)}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center py-8 text-center px-4">
            <GitCommit className="w-8 h-8 text-muted-foreground opacity-50 mb-2" />
            <p className="text-xs text-muted-foreground">No timeline events</p>
            <p className="text-[10px] text-muted-foreground mt-1 opacity-75">
              Git history will appear here
            </p>
          </div>
        )}
      </div>
    </div>
  );
};

