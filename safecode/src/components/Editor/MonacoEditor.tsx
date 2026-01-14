import React, { useRef, useEffect } from 'react';
import Editor from '@monaco-editor/react';
import { EditorTab } from '../../types';
import { useIDE } from '../../contexts/IDEContext';
import './MonacoEditor.css';

interface MonacoEditorProps {
  tab: EditorTab;
}

const MonacoEditor: React.FC<MonacoEditorProps> = ({ tab }) => {
  const { updateTabContent } = useIDE();

  const handleEditorChange = (value: string | undefined) => {
    if (value !== undefined) {
      updateTabContent(tab.id, value);
    }
  };

  return (
    <div className="monaco-editor-wrapper">
      <Editor
        height="100%"
        language={tab.language || 'plaintext'}
        value={tab.content}
        onChange={handleEditorChange}
        theme="vs-dark"
        options={{
          fontSize: 13,
          fontFamily: 'JetBrains Mono, Consolas, monospace',
          minimap: { enabled: true },
          lineNumbers: 'on',
          scrollBeyondLastLine: false,
          automaticLayout: true,
          tabSize: 2,
          wordWrap: 'on',
          cursorBlinking: 'smooth',
          cursorSmoothCaretAnimation: 'on'
        }}
      />
    </div>
  );
};

export default MonacoEditor;



