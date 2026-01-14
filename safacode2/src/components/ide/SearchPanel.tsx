import { Search, FileText } from 'lucide-react';
import { useState, useMemo } from 'react';
import { useIDEStore } from '@/stores/ideStore';
import type { FileNode } from '@/types/ide';

interface SearchResult {
  file: FileNode;
  matches: Array<{ line: number; content: string; column: number }>;
}

export const SearchPanel: React.FC = () => {
  const [query, setQuery] = useState('');
  const [caseSensitive, setCaseSensitive] = useState(false);
  const { files, openFile } = useIDEStore();

  // Flatten all files
  const flattenFiles = (nodes: FileNode[]): FileNode[] => {
    let result: FileNode[] = [];
    for (const node of nodes) {
      if (node.type === 'file' && node.content) {
        result.push(node);
      }
      if (node.children) {
        result = [...result, ...flattenFiles(node.children)];
      }
    }
    return result;
  };

  const allFiles = useMemo(() => flattenFiles(files), [files]);

  // Search in files
  const searchResults = useMemo((): SearchResult[] => {
    if (!query.trim()) return [];

    const results: SearchResult[] = [];
    const searchQuery = caseSensitive ? query : query.toLowerCase();

    allFiles.forEach(file => {
      if (!file.content) return;

      const lines = file.content.split('\n');
      const matches: Array<{ line: number; content: string; column: number }> = [];

      lines.forEach((line, index) => {
        const searchLine = caseSensitive ? line : line.toLowerCase();
        let column = searchLine.indexOf(searchQuery);
        
        while (column !== -1) {
          matches.push({
            line: index + 1,
            content: line,
            column: column + 1,
          });
          column = searchLine.indexOf(searchQuery, column + 1);
        }
      });

      if (matches.length > 0) {
        results.push({ file, matches });
      }
    });

    return results;
  }, [query, caseSensitive, allFiles]);

  const totalMatches = useMemo(() => 
    searchResults.reduce((sum, result) => sum + result.matches.length, 0),
    [searchResults]
  );

  return (
    <div className="h-full flex flex-col">
      <div className="flex items-center justify-between px-3 py-2 border-b border-panel-border">
        <span className="text-xs font-medium uppercase tracking-wider text-muted-foreground">
          Search
        </span>
        {query && (
          <span className="text-xs text-muted-foreground">
            {totalMatches} {totalMatches === 1 ? 'match' : 'matches'}
          </span>
        )}
      </div>
      
      <div className="p-2 space-y-2">
        <div className="flex items-center gap-2 bg-input border border-border rounded px-2 focus-within:border-primary">
          <Search className="w-4 h-4 text-muted-foreground" />
          <input
            type="text"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search in files..."
            className="flex-1 py-1.5 bg-transparent outline-none text-sm"
          />
        </div>
        <div className="flex items-center gap-2">
          <label className="flex items-center gap-1.5 text-xs text-muted-foreground cursor-pointer">
            <input
              type="checkbox"
              checked={caseSensitive}
              onChange={(e) => setCaseSensitive(e.target.checked)}
              className="w-3.5 h-3.5"
            />
            Match case
          </label>
        </div>
      </div>

      <div className="flex-1 overflow-auto scrollbar-thin">
        {!query ? (
          <div className="text-center py-8 text-muted-foreground text-sm px-2">
            <Search className="w-8 h-8 mx-auto mb-2 opacity-50" />
            <p>Enter a search term to find in files</p>
          </div>
        ) : searchResults.length === 0 ? (
          <div className="text-sm text-muted-foreground py-4 text-center px-2">
            No results found for "{query}"
          </div>
        ) : (
          <div className="px-2 pb-2">
            {searchResults.map((result) => (
              <div key={result.file.id} className="mb-3">
                <div 
                  onClick={() => openFile(result.file)}
                  className="flex items-center gap-2 px-2 py-1.5 hover:bg-sidebar-hover cursor-pointer rounded mb-1"
                >
                  <FileText className="w-4 h-4 text-primary flex-shrink-0" />
                  <span className="text-sm font-medium truncate">{result.file.name}</span>
                  <span className="text-xs text-muted-foreground ml-auto">
                    {result.matches.length} {result.matches.length === 1 ? 'match' : 'matches'}
                  </span>
                </div>
                <div className="ml-6 space-y-0.5">
                  {result.matches.slice(0, 5).map((match, idx) => (
                    <div
                      key={idx}
                      onClick={() => {
                        openFile(result.file);
                        // Focus editor and navigate to line
                        setTimeout(() => {
                          const editor = (window as any).monacoEditor;
                          if (editor) {
                            editor.setPosition({ lineNumber: match.line, column: match.column });
                            editor.revealLineInCenter(match.line);
                          }
                        }, 100);
                      }}
                      className="px-2 py-1 text-xs hover:bg-sidebar-hover cursor-pointer rounded"
                    >
                      <span className="text-muted-foreground mr-2">{match.line}:</span>
                      <span className="font-mono">
                        {match.content.substring(0, 80)}
                        {match.content.length > 80 ? '...' : ''}
                      </span>
                    </div>
                  ))}
                  {result.matches.length > 5 && (
                    <div className="text-xs text-muted-foreground px-2 py-1">
                      +{result.matches.length - 5} more matches
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};
