import { useState, useEffect, useRef } from 'react';
import { Search, Replace, X, ChevronDown, ChevronUp, CaseSensitive, Regex, WholeWord } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';

type MonacoEditor = any; // Monaco editor type from window

export const FindReplace: React.FC = () => {
  const { findReplaceOpen, toggleFindReplace, activeTabId, tabs } = useIDEStore();
  const [showReplace, setShowReplace] = useState(false);
  const [findValue, setFindValue] = useState('');
  const [replaceValue, setReplaceValue] = useState('');
  const [caseSensitive, setCaseSensitive] = useState(false);
  const [wholeWord, setWholeWord] = useState(false);
  const [regex, setRegex] = useState(false);
  const [matchCount, setMatchCount] = useState(0);
  const [currentMatch, setCurrentMatch] = useState(0);
  const editorRef = useRef<MonacoEditor | null>(null);

  // Get editor instance from window (set by CodeEditor)
  useEffect(() => {
    const getEditor = () => {
      const editor = (window as any).monacoEditor;
      if (editor) {
        editorRef.current = editor;
      }
    };
    getEditor();
    const interval = setInterval(getEditor, 100);
    return () => clearInterval(interval);
  }, [activeTabId]);

  // Update find when value changes
  useEffect(() => {
    if (!editorRef.current || !findValue) {
      setMatchCount(0);
      setCurrentMatch(0);
      return;
    }

    const editor = editorRef.current;
    const model = editor.getModel();
    if (!model) return;

    try {
      const matches = model.findMatches(findValue, false, regex, caseSensitive, wholeWord ? 'word' : null, false);
      setMatchCount(matches.length);
      setCurrentMatch(matches.length > 0 ? 1 : 0);

      // Highlight matches
      const monaco = (window as any).monaco;
      if (monaco) {
        editor.setModelMarkers(
          model,
          'findMatches',
          matches.map(match => ({
            startLineNumber: match.range.startLineNumber,
            startColumn: match.range.startColumn,
            endLineNumber: match.range.endLineNumber,
            endColumn: match.range.endColumn,
            message: 'Match',
            severity: monaco.MarkerSeverity?.Info || 1,
          }))
        );
      }

      // Navigate to first match
      if (matches.length > 0) {
        editor.setPosition(matches[0].range.getStartPosition());
        editor.revealLineInCenter(matches[0].range.startLineNumber);
      }
    } catch (error) {
      // Invalid regex
      setMatchCount(0);
    }
  }, [findValue, caseSensitive, wholeWord, regex, activeTabId]);

  const navigateMatch = (direction: 'next' | 'prev') => {
    if (!editorRef.current || !findValue) return;

    const editor = editorRef.current;
    const model = editor.getModel();
    if (!model) return;

    try {
      const matches = model.findMatches(findValue, false, regex, caseSensitive, wholeWord ? 'word' : null, false);
      if (matches.length === 0) return;

      const currentPos = editor.getPosition();
      if (!currentPos) return;

      let nextIndex = 0;
      if (direction === 'next') {
        nextIndex = matches.findIndex(m => 
          m.range.startLineNumber > currentPos.lineNumber || 
          (m.range.startLineNumber === currentPos.lineNumber && m.range.startColumn > currentPos.column)
        );
        if (nextIndex === -1) nextIndex = 0;
      } else {
        for (let i = matches.length - 1; i >= 0; i--) {
          const match = matches[i];
          if (match.range.startLineNumber < currentPos.lineNumber || 
              (match.range.startLineNumber === currentPos.lineNumber && match.range.startColumn < currentPos.column)) {
            nextIndex = i;
            break;
          }
        }
      }

      const match = matches[nextIndex];
      editor.setPosition(match.range.getStartPosition());
      editor.setSelection(match.range);
      editor.revealLineInCenter(match.range.startLineNumber);
      setCurrentMatch(nextIndex + 1);
    } catch (error) {
      // Invalid regex
    }
  };

  const handleReplace = () => {
    if (!editorRef.current || !findValue) return;

    const editor = editorRef.current;
    const selection = editor.getSelection();
    if (!selection) return;

    const model = editor.getModel();
    if (!model) return;

    const selectedText = model.getValueInRange(selection);
    try {
      let match = false;
      if (regex) {
        const regexObj = new RegExp(findValue, caseSensitive ? 'g' : 'gi');
        match = regexObj.test(selectedText);
      } else {
        match = caseSensitive 
          ? selectedText === findValue
          : selectedText.toLowerCase() === findValue.toLowerCase();
      }

      if (match) {
        editor.executeEdits('find-replace', [{
          range: selection,
          text: replaceValue,
        }]);
        navigateMatch('next');
      }
    } catch (error) {
      // Invalid regex
    }
  };

  const handleReplaceAll = () => {
    if (!editorRef.current || !findValue) return;

    const editor = editorRef.current;
    const model = editor.getModel();
    if (!model) return;

    try {
      const matches = model.findMatches(findValue, false, regex, caseSensitive, wholeWord ? 'word' : null, false);
      if (matches.length === 0) return;

      const edits = matches.map(match => ({
        range: match.range,
        text: replaceValue,
      }));

      editor.executeEdits('find-replace-all', edits);
      setMatchCount(0);
    } catch (error) {
      // Invalid regex
    }
  };

  if (!findReplaceOpen) return null;

  return (
    <div className="absolute top-12 right-4 z-40 animate-slide-up">
      <div className="border border-border rounded-lg shadow-xl p-3 w-96" style={{ backgroundColor: '#000000' }}>
        <div className="flex items-center gap-2 mb-2">
          {/* Find input */}
          <div className="flex-1 flex items-center gap-2 bg-input border border-border rounded px-2 focus-within:border-primary">
            <Search className="w-4 h-4 text-muted-foreground flex-shrink-0" />
            <input
              type="text"
              value={findValue}
              onChange={(e) => setFindValue(e.target.value)}
              placeholder="Find"
              className="flex-1 py-1.5 bg-transparent outline-none text-sm"
              autoFocus
            />
            <div className="flex items-center gap-0.5">
              <button
                onClick={() => setCaseSensitive(!caseSensitive)}
                className={`p-1 rounded transition-colors ${caseSensitive ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                title="Match Case"
              >
                <CaseSensitive className="w-4 h-4" />
              </button>
              <button
                onClick={() => setWholeWord(!wholeWord)}
                className={`p-1 rounded transition-colors ${wholeWord ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                title="Match Whole Word"
              >
                <WholeWord className="w-4 h-4" />
              </button>
              <button
                onClick={() => setRegex(!regex)}
                className={`p-1 rounded transition-colors ${regex ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                title="Use Regular Expression"
              >
                <Regex className="w-4 h-4" />
              </button>
            </div>
          </div>

          {/* Results */}
          <span className="text-xs text-muted-foreground whitespace-nowrap">
            {findValue && matchCount > 0 ? `${currentMatch}/${matchCount}` : findValue ? 'No results' : ''}
          </span>

          {/* Navigation */}
          <div className="flex items-center gap-0.5">
            <button 
              onClick={() => navigateMatch('prev')}
              className="p-1 rounded hover:bg-muted transition-colors" 
              title="Previous Match"
              disabled={!findValue || matchCount === 0}
            >
              <ChevronUp className="w-4 h-4" />
            </button>
            <button 
              onClick={() => navigateMatch('next')}
              className="p-1 rounded hover:bg-muted transition-colors" 
              title="Next Match"
              disabled={!findValue || matchCount === 0}
            >
              <ChevronDown className="w-4 h-4" />
            </button>
          </div>

          {/* Toggle replace */}
          <button
            onClick={() => setShowReplace(!showReplace)}
            className={`p-1 rounded transition-colors ${showReplace ? 'bg-muted' : 'hover:bg-muted'}`}
            title="Toggle Replace"
          >
            <Replace className="w-4 h-4" />
          </button>

          {/* Close */}
          <button
            onClick={toggleFindReplace}
            className="p-1 rounded hover:bg-muted transition-colors"
            title="Close"
          >
            <X className="w-4 h-4" />
          </button>
        </div>

        {/* Replace row */}
        {showReplace && (
          <div className="flex items-center gap-2">
            <div className="flex-1 flex items-center gap-2 bg-input border border-border rounded px-2 focus-within:border-primary">
              <Replace className="w-4 h-4 text-muted-foreground flex-shrink-0" />
              <input
                type="text"
                value={replaceValue}
                onChange={(e) => setReplaceValue(e.target.value)}
                placeholder="Replace"
                className="flex-1 py-1.5 bg-transparent outline-none text-sm"
              />
            </div>
            <button 
              onClick={handleReplace}
              className="px-2 py-1 text-xs rounded bg-muted hover:bg-muted/80 transition-colors"
              disabled={!findValue || !replaceValue}
            >
              Replace
            </button>
            <button 
              onClick={handleReplaceAll}
              className="px-2 py-1 text-xs rounded bg-muted hover:bg-muted/80 transition-colors"
              disabled={!findValue || !replaceValue}
            >
              All
            </button>
          </div>
        )}
      </div>
    </div>
  );
};
