import React from 'react';
import { useIDE } from '../../contexts/IDEContext';
import MonacoEditor from '../Editor/MonacoEditor';
import Tabs from '../Editor/Tabs';
import './EditorArea.css';

const EditorArea: React.FC = () => {
  const { state } = useIDE();
  const activeTab = state.openTabs.find(tab => tab.id === state.activeTab);

  return (
    <section className="ide-editor-area">
      {state.openTabs.length > 0 && <Tabs />}
      <div className="editor-container">
        {activeTab ? (
          <MonacoEditor tab={activeTab} />
        ) : (
          <div className="editor-welcome">
            <h2>Welcome to SafeCode IDE</h2>
            <p>Open a file to start editing</p>
          </div>
        )}
      </div>
    </section>
  );
};

export default EditorArea;



