import { useRef, useCallback, useEffect } from 'react';
import Editor, { OnMount, OnChange } from '@monaco-editor/react';
import { useIDEStore } from '@/stores/ideStore';

type MonacoEditor = Parameters<OnMount>[0];

export const CodeEditor: React.FC = () => {
  const { tabs, activeTabId, updateTabContent, updateCursorPosition, saveTab, settings, addProblem, removeProblem } = useIDEStore();
  const editorRef = useRef<MonacoEditor | null>(null);
  
  const activeTab = tabs.find(t => t.id === activeTabId);

  const handleEditorMount: OnMount = (editor, monaco) => {
    editorRef.current = editor;
    // Expose editor globally for FindReplace
    (window as any).monacoEditor = editor;
    
    // Get syntax colors from settings
    const syntaxColors = settings.syntaxColors || {
      comment: '6b7280',
      keyword: '60a5fa',
      string: '4ade80',
      number: 'fb923c',
      type: 'fbbf24',
      function: '60a5fa',
      variable: '38bdf8',
      operator: 'f472b6',
    };
    
    // Custom theme - Deep Black Minimalist
    monaco.editor.defineTheme('safecode-dark', {
      base: 'vs-dark',
      inherit: true,
      rules: [
        { token: 'comment', foreground: syntaxColors.comment, fontStyle: 'italic' },
        { token: 'keyword', foreground: syntaxColors.keyword },
        { token: 'string', foreground: syntaxColors.string },
        { token: 'number', foreground: syntaxColors.number },
        { token: 'type', foreground: syntaxColors.type },
        { token: 'function', foreground: syntaxColors.function },
        { token: 'variable', foreground: syntaxColors.variable },
        { token: 'operator', foreground: syntaxColors.operator },
      ],
      colors: {
        'editor.background': '#000000',
        'editor.foreground': '#f5f5f5',
        'editorLineNumber.foreground': '#404040',
        'editorLineNumber.activeForeground': '#808080',
        'editor.lineHighlightBackground': '#0a0a0a',
        'editor.selectionBackground': '#3b82f640',
        'editorCursor.foreground': '#3b82f6',
        'editorIndentGuide.background': '#1a1a1a',
        'editorIndentGuide.activeBackground': '#2a2a2a',
        'editorWhitespace.foreground': '#2a2a2a',
        'editorRuler.foreground': '#1a1a1a',
        'scrollbarSlider.background': '#2a2a2a',
        'scrollbarSlider.hoverBackground': '#3a3a3a',
        'editorWidget.background': '#0a0a0a',
        'editorWidget.border': '#1a1a1a',
      },
    });
    
    monaco.editor.setTheme('safecode-dark');

    // Keyboard shortcuts
    editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KeyS, () => {
      if (activeTabId) {
        saveTab(activeTabId);
      }
    });

    // Track cursor position
    editor.onDidChangeCursorPosition((e) => {
      if (activeTabId) {
        updateCursorPosition(activeTabId, e.position.lineNumber, e.position.column);
      }
    });
    
    // Store monaco instance for theme updates
    (window as any).monacoInstance = monaco;
    
    // Listen for markers (errors/warnings) changes
    const updateProblems = () => {
      if (!activeTabId || !activeTab) return;
      
      const model = editor.getModel();
      if (!model) return;
      
      const markers = monaco.editor.getModelMarkers({ resource: model.uri });
      const fileName = activeTab.name;
      
      // Remove existing problems for this file
      const { problems, removeProblem } = useIDEStore.getState();
      problems
        .filter(p => p.file === fileName)
        .forEach(p => removeProblem(p.id));
      
      // Add new problems
      markers.forEach(marker => {
        const severity = marker.severity;
        const type = severity === monaco.MarkerSeverity.Error ? 'error' :
                     severity === monaco.MarkerSeverity.Warning ? 'warning' : 'info';
        
        addProblem({
          type,
          message: marker.message,
          file: fileName,
          line: marker.startLineNumber,
          column: marker.startColumn,
          source: marker.source || 'TypeScript'
        });
      });
    };
    
    // Update problems when markers change
    const disposable = monaco.editor.onDidChangeMarkers(() => {
      updateProblems();
    });
    
    // Initial update
    setTimeout(updateProblems, 500);
    
    // Cleanup
    return () => {
      disposable.dispose();
    };
  };
  
  // Update theme when syntax colors change
  useEffect(() => {
    const monaco = (window as any).monacoInstance;
    if (!monaco || !settings.syntaxColors) return;
    
    const syntaxColors = settings.syntaxColors;
    monaco.editor.defineTheme('safecode-dark', {
      base: 'vs-dark',
      inherit: true,
      rules: [
        { token: 'comment', foreground: syntaxColors.comment, fontStyle: 'italic' },
        { token: 'keyword', foreground: syntaxColors.keyword },
        { token: 'string', foreground: syntaxColors.string },
        { token: 'number', foreground: syntaxColors.number },
        { token: 'type', foreground: syntaxColors.type },
        { token: 'function', foreground: syntaxColors.function },
        { token: 'variable', foreground: syntaxColors.variable },
        { token: 'operator', foreground: syntaxColors.operator },
      ],
      colors: {
        'editor.background': '#000000',
        'editor.foreground': '#f5f5f5',
        'editorLineNumber.foreground': '#404040',
        'editorLineNumber.activeForeground': '#808080',
        'editor.lineHighlightBackground': '#0a0a0a',
        'editor.selectionBackground': '#3b82f640',
        'editorCursor.foreground': '#3b82f6',
        'editorIndentGuide.background': '#1a1a1a',
        'editorIndentGuide.activeBackground': '#2a2a2a',
        'editorWhitespace.foreground': '#2a2a2a',
        'editorRuler.foreground': '#1a1a1a',
        'scrollbarSlider.background': '#2a2a2a',
        'scrollbarSlider.hoverBackground': '#3a3a3a',
        'editorWidget.background': '#0a0a0a',
        'editorWidget.border': '#1a1a1a',
      },
    });
    monaco.editor.setTheme('safecode-dark');
  }, [settings.syntaxColors]);

  const handleChange: OnChange = useCallback((value) => {
    if (activeTabId && value !== undefined) {
      updateTabContent(activeTabId, value);
    }
  }, [activeTabId, updateTabContent]);

  if (!activeTab) {
    return (
      <div className="flex-1 flex items-center justify-center text-muted-foreground" style={{ backgroundColor: '#000000' }}>
        <div className="text-center">
          <p className="text-lg">No file open</p>
          <p className="text-sm mt-1">Open a file from the explorer</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex-1 h-full">
      <Editor
        height="100%"
        language={activeTab.language}
        value={activeTab.content}
        onChange={handleChange}
        onMount={handleEditorMount}
        options={{
          fontSize: settings.fontSize,
          tabSize: settings.tabSize,
          wordWrap: settings.wordWrap ? 'on' : 'off',
          minimap: { enabled: settings.minimap },
          scrollBeyondLastLine: false,
          automaticLayout: true,
          lineNumbers: 'on',
          folding: true,
          foldingHighlight: true,
          renderLineHighlight: 'line',
          cursorBlinking: 'smooth',
          cursorSmoothCaretAnimation: 'on',
          smoothScrolling: true,
          fontFamily: "'JetBrains Mono', 'Fira Code', Consolas, monospace",
          fontLigatures: true,
          padding: { top: 12 },
          suggest: {
            showMethods: true,
            showFunctions: true,
            showConstructors: true,
            showFields: true,
            showVariables: true,
            showClasses: true,
            showStructs: true,
            showInterfaces: true,
            showModules: true,
            showProperties: true,
            showEvents: true,
            showOperators: true,
            showUnits: true,
            showValues: true,
            showConstants: true,
            showEnums: true,
            showEnumMembers: true,
            showKeywords: true,
            showWords: true,
            showColors: true,
            showFiles: true,
            showReferences: true,
            showFolders: true,
            showTypeParameters: true,
            showSnippets: true,
          },
        }}
        loading={
          <div className="flex items-center justify-center h-full" style={{ backgroundColor: '#000000' }}>
            <div className="animate-pulse text-muted-foreground">Loading editor...</div>
          </div>
        }
      />
    </div>
  );
};
