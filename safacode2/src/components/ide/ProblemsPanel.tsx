import React from 'react';
import { X, AlertCircle, AlertTriangle, Info } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';

export interface Problem {
  id: string;
  type: 'error' | 'warning' | 'info';
  message: string;
  file?: string;
  line?: number;
  column?: number;
  source?: string;
}

interface ProblemsPanelProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  problems: Problem[];
}

export const ProblemsPanel: React.FC<ProblemsPanelProps> = ({ open, onOpenChange, problems }) => {
  const errors = problems.filter(p => p.type === 'error');
  const warnings = problems.filter(p => p.type === 'warning');
  const infos = problems.filter(p => p.type === 'info');

  const getIcon = (type: Problem['type']) => {
    switch (type) {
      case 'error':
        return <AlertCircle className="w-4 h-4 text-destructive" />;
      case 'warning':
        return <AlertTriangle className="w-4 h-4 text-warning" />;
      case 'info':
        return <Info className="w-4 h-4 text-primary" />;
    }
  };

  const renderProblem = (problem: Problem) => (
    <div
      key={problem.id}
      className="flex items-start gap-3 p-2 hover:bg-muted/50 rounded transition-colors cursor-pointer group"
      onClick={() => {
        // Navigate to file/line if available
        if (problem.file && problem.line) {
          // This would open the file in the editor
          console.log('Navigate to:', problem.file, problem.line);
        }
      }}
    >
      <div className="flex-shrink-0 mt-0.5">
        {getIcon(problem.type)}
      </div>
      <div className="flex-1 min-w-0">
        <div className="text-sm text-foreground">{problem.message}</div>
        {problem.file && (
          <div className="text-xs text-muted-foreground mt-1">
            {problem.file}
            {problem.line && `:${problem.line}`}
            {problem.column && `:${problem.column}`}
          </div>
        )}
        {problem.source && (
          <div className="text-xs text-muted-foreground mt-0.5">
            {problem.source}
          </div>
        )}
      </div>
    </div>
  );

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh] flex flex-col" style={{ backgroundColor: '#000000' }}>
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <AlertCircle className="w-5 h-5" />
            Problems
          </DialogTitle>
          <DialogDescription>
            {problems.length === 0 
              ? 'No problems detected' 
              : `${errors.length} error${errors.length !== 1 ? 's' : ''}, ${warnings.length} warning${warnings.length !== 1 ? 's' : ''}, ${infos.length} info${infos.length !== 1 ? 's' : ''}`
            }
          </DialogDescription>
        </DialogHeader>

        <div className="flex-1 overflow-auto hide-scrollbar mt-4">
          {problems.length === 0 ? (
            <div className="text-center py-12 text-muted-foreground">
              <AlertCircle className="w-12 h-12 mx-auto mb-4 opacity-50" />
              <p className="text-sm">No problems detected in the workspace</p>
            </div>
          ) : (
            <div className="space-y-1">
              {errors.length > 0 && (
                <div>
                  <div className="text-xs font-semibold text-destructive mb-2 px-2">
                    Errors ({errors.length})
                  </div>
                  <div className="space-y-0.5">
                    {errors.map(renderProblem)}
                  </div>
                </div>
              )}

              {warnings.length > 0 && (
                <div className={errors.length > 0 ? 'mt-4' : ''}>
                  <div className="text-xs font-semibold text-warning mb-2 px-2">
                    Warnings ({warnings.length})
                  </div>
                  <div className="space-y-0.5">
                    {warnings.map(renderProblem)}
                  </div>
                </div>
              )}

              {infos.length > 0 && (
                <div className={(errors.length > 0 || warnings.length > 0) ? 'mt-4' : ''}>
                  <div className="text-xs font-semibold text-primary mb-2 px-2">
                    Info ({infos.length})
                  </div>
                  <div className="space-y-0.5">
                    {infos.map(renderProblem)}
                  </div>
                </div>
              )}
            </div>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
};

