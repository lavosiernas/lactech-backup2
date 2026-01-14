import React from 'react';
import { Folder, Search, GitBranch, Puzzle } from 'lucide-react';
import { useIDE } from '../../contexts/IDEContext';
import Explorer from '../Sidebar/Explorer';
import './Sidebar.css';

const Sidebar: React.FC = () => {
  const { state, setSidebarView } = useIDE();

  const views = [
    { id: 'explorer' as const, icon: Folder, label: 'Explorer' },
    { id: 'search' as const, icon: Search, label: 'Search' },
    { id: 'git' as const, icon: GitBranch, label: 'Source Control' },
    { id: 'extensions' as const, icon: Puzzle, label: 'Extensions' }
  ];

  return (
    <aside className="ide-sidebar">
      <div className="sidebar-activity-bar">
        {views.map(view => {
          const Icon = view.icon;
          return (
            <button
              key={view.id}
              className={`sidebar-tab ${state.activeSidebarView === view.id ? 'active' : ''}`}
              onClick={() => setSidebarView(view.id)}
              title={view.label}
            >
              <Icon size={20} />
            </button>
          );
        })}
      </div>
      <div className="sidebar-panel">
        {state.activeSidebarView === 'explorer' && <Explorer />}
        {state.activeSidebarView === 'search' && (
          <div className="sidebar-content">
            <div className="sidebar-section">
              <div className="section-header">
                <span>SEARCH</span>
              </div>
              <div className="search-container">
                <input type="text" className="search-input" placeholder="Search in files..." />
                <div className="empty-state">No results</div>
              </div>
            </div>
          </div>
        )}
        {state.activeSidebarView === 'git' && (
          <div className="sidebar-content">
            <div className="sidebar-section">
              <div className="section-header">
                <span>SOURCE CONTROL</span>
              </div>
              <div className="empty-state">Git integration coming soon</div>
            </div>
          </div>
        )}
        {state.activeSidebarView === 'extensions' && (
          <div className="sidebar-content">
            <div className="sidebar-section">
              <div className="section-header">
                <span>EXTENSIONS</span>
              </div>
              <div className="empty-state">Extension marketplace coming soon</div>
            </div>
          </div>
        )}
      </div>
    </aside>
  );
};

export default Sidebar;


