import React from 'react';
import { X } from 'lucide-react';
import { useIDE } from '../../contexts/IDEContext';
import './Tabs.css';

const Tabs: React.FC = () => {
  const { state, setActiveTab, closeTab } = useIDE();

  return (
    <div className="editor-tabs">
      {state.openTabs.map(tab => (
        <div
          key={tab.id}
          className={`editor-tab ${state.activeTab === tab.id ? 'active' : ''}`}
          onClick={() => setActiveTab(tab.id)}
        >
          <span className="tab-label">
            {tab.fileName}
            {tab.isDirty && <span className="dirty-indicator">●</span>}
          </span>
          <button
            className="tab-close"
            onClick={(e) => {
              e.stopPropagation();
              closeTab(tab.id);
            }}
          >
            <X size={14} />
          </button>
        </div>
      ))}
    </div>
  );
};

export default Tabs;



