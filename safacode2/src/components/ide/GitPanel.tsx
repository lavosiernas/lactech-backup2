import { GitBranch, GitCommit, Plus, Minus, RotateCcw, Check } from 'lucide-react';
import { useState, useEffect } from 'react';
import { useIDEStore } from '@/stores/ideStore';

export const GitPanel: React.FC = () => {
  const { gitStatus, stageFile, unstageFile, commitChanges, setGitStatus, files } = useIDEStore();
  const [commitMessage, setCommitMessage] = useState('');

  // Check for git repository when files change
  useEffect(() => {
    if (files.length > 0) {
      // In a real implementation, would check if .git folder exists
      // For now, just reset git status when folder changes
      setGitStatus({
        branch: '',
        staged: [],
        modified: [],
        untracked: []
      });
    }
  }, [files, setGitStatus]);

  return (
    <div className="h-full flex flex-col">
      <div className="flex items-center justify-between px-2 py-1.5">
        <span className="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
          Source Control
        </span>
        <div className="flex items-center gap-0.5">
          <button 
            onClick={() => {
              if (gitStatus.staged.length > 0 && commitMessage.trim()) {
                commitChanges(commitMessage);
                setCommitMessage('');
              }
            }}
            className="p-1 hover:bg-sidebar-hover rounded transition-colors" 
            title="Commit"
            disabled={gitStatus.staged.length === 0 || !commitMessage.trim()}
          >
            <Check className="w-4 h-4 text-muted-foreground" />
          </button>
          <button 
            onClick={() => {
              // Refresh git status
              // In a real implementation, would check git status
              setGitStatus({
                branch: gitStatus.branch || '',
                staged: gitStatus.staged,
                modified: gitStatus.modified,
                untracked: gitStatus.untracked
              });
            }}
            className="p-1 hover:bg-sidebar-hover rounded transition-colors" 
            title="Refresh"
          >
            <RotateCcw className="w-4 h-4 text-muted-foreground" />
          </button>
        </div>
      </div>

      <div className="flex-1 overflow-auto hide-scrollbar p-2">
        {/* Branch info */}
        {gitStatus.branch ? (
          <div className="flex items-center gap-2 px-2 py-1.5 mb-3">
            <GitBranch className="w-4 h-4 text-primary" />
            <span className="text-sm font-medium">{gitStatus.branch}</span>
          </div>
        ) : (
          <div className="px-2 py-1.5 mb-3 text-xs text-muted-foreground">
            No git repository detected
          </div>
        )}

        {/* Commit message */}
        {gitStatus.staged.length > 0 && (
          <div className="px-2 mb-3">
            <textarea
              value={commitMessage}
              onChange={(e) => setCommitMessage(e.target.value)}
              placeholder="Commit message"
              className="w-full h-16 px-2 py-1.5 text-sm bg-input border border-border rounded resize-none focus:outline-none focus:border-primary"
            />
            <button 
              onClick={() => {
                if (commitMessage.trim()) {
                  commitChanges(commitMessage);
                  setCommitMessage('');
                }
              }}
              disabled={!commitMessage.trim()}
              className="w-full mt-2 py-1.5 text-sm bg-primary text-primary-foreground rounded hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <GitCommit className="w-4 h-4 inline mr-2" />
              Commit {gitStatus.staged.length} {gitStatus.staged.length === 1 ? 'file' : 'files'}
            </button>
          </div>
        )}

        {/* Staged changes */}
        {gitStatus.staged.length > 0 && (
          <div className="mb-3">
            <div className="flex items-center justify-between px-2 py-1">
              <span className="text-xs text-muted-foreground uppercase">Staged Changes</span>
              <span className="text-xs bg-success/20 text-success px-1.5 rounded">
                {gitStatus.staged.length}
              </span>
            </div>
            {gitStatus.staged.map((file) => (
              <div key={file} className="ide-sidebar-item">
                <Plus className="w-3 h-3 text-success" />
                <span className="text-sm truncate">{file}</span>
                <button 
                  onClick={(e) => {
                    e.stopPropagation();
                    unstageFile(file);
                  }}
                  className="ml-auto p-0.5 hover:bg-muted rounded"
                  title="Unstage"
                >
                  <Minus className="w-3 h-3" />
                </button>
              </div>
            ))}
          </div>
        )}

        {/* Modified files */}
        {gitStatus.modified.length > 0 && (
          <div className="mb-3">
            <div className="flex items-center justify-between px-2 py-1">
              <span className="text-xs text-muted-foreground uppercase">Changes</span>
              <span className="text-xs bg-warning/20 text-warning px-1.5 rounded">
                {gitStatus.modified.length}
              </span>
            </div>
            {gitStatus.modified.map((file) => (
              <div key={file} className="ide-sidebar-item">
                <span className="w-3 h-3 text-xs text-warning font-bold">M</span>
                <span className="text-sm truncate">{file}</span>
                <button 
                  onClick={(e) => {
                    e.stopPropagation();
                    stageFile(file);
                  }}
                  className="ml-auto p-0.5 hover:bg-muted rounded" 
                  title="Stage"
                >
                  <Plus className="w-3 h-3" />
                </button>
              </div>
            ))}
          </div>
        )}

        {/* Untracked files */}
        {gitStatus.untracked.length > 0 && (
          <div>
            <div className="flex items-center justify-between px-2 py-1">
              <span className="text-xs text-muted-foreground uppercase">Untracked</span>
              <span className="text-xs bg-muted text-muted-foreground px-1.5 rounded">
                {gitStatus.untracked.length}
              </span>
            </div>
            {gitStatus.untracked.map((file) => (
              <div key={file} className="ide-sidebar-item">
                <span className="w-3 h-3 text-xs text-muted-foreground font-bold">U</span>
                <span className="text-sm truncate">{file}</span>
                <button 
                  onClick={(e) => {
                    e.stopPropagation();
                    stageFile(file);
                  }}
                  className="ml-auto p-0.5 hover:bg-muted rounded" 
                  title="Stage"
                >
                  <Plus className="w-3 h-3" />
                </button>
              </div>
            ))}
          </div>
        )}

        {gitStatus.staged.length === 0 && 
         gitStatus.modified.length === 0 && 
         gitStatus.untracked.length === 0 && (
          <div className="text-center py-8 text-muted-foreground text-sm">
            <GitBranch className="w-8 h-8 mx-auto mb-2 opacity-50" />
            <p>No changes</p>
          </div>
        )}
      </div>
    </div>
  );
};
