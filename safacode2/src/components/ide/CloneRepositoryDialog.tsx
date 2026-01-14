import { useState } from 'react';
import { GitBranch, Loader2 } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogDescription,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';

interface CloneRepositoryDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onConfirm: (url: string, destination: string) => Promise<void>;
}

export const CloneRepositoryDialog: React.FC<CloneRepositoryDialogProps> = ({
  open,
  onOpenChange,
  onConfirm,
}) => {
  const [url, setUrl] = useState('');
  const [destination, setDestination] = useState('');
  const [isCloning, setIsCloning] = useState(false);
  const [error, setError] = useState('');

  const validateUrl = (url: string): boolean => {
    if (!url.trim()) return false;
    
    // Aceita URLs do GitHub, GitLab, Bitbucket, etc.
    const gitUrlPattern = /^(https?:\/\/|git@)([\w\.-]+@)?([\w\.-]+)(\/|:)([\w\.\/-]+)(\.git)?$/;
    return gitUrlPattern.test(url) || url.includes('github.com') || url.includes('gitlab.com') || url.includes('bitbucket.org');
  };

  const handleConfirm = async () => {
    if (!validateUrl(url)) {
      setError('Please enter a valid Git repository URL');
      return;
    }

    setIsCloning(true);
    setError('');

    try {
      await onConfirm(url.trim(), destination.trim() || extractRepoName(url));
      setUrl('');
      setDestination('');
      onOpenChange(false);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to clone repository');
    } finally {
      setIsCloning(false);
    }
  };

  const extractRepoName = (url: string): string => {
    try {
      const match = url.match(/\/([^\/]+?)(?:\.git)?$/);
      return match ? match[1] : 'repository';
    } catch {
      return 'repository';
    }
  };

  const handleUrlChange = (value: string) => {
    setUrl(value);
    setError('');
    // Auto-preencher destination se estiver vazio
    if (!destination && value) {
      setDestination(extractRepoName(value));
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <GitBranch className="w-4 h-4" />
            Clone Repository
          </DialogTitle>
          <DialogDescription>
            Enter the Git repository URL to clone. The repository will be cloned to your workspace.
          </DialogDescription>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div>
            <Label htmlFor="url">Repository URL</Label>
            <Input
              id="url"
              value={url}
              onChange={(e) => handleUrlChange(e.target.value)}
              placeholder="https://github.com/username/repo.git"
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !isCloning) handleConfirm();
                if (e.key === 'Escape') onOpenChange(false);
              }}
              disabled={isCloning}
              autoFocus
              className={error ? 'border-destructive' : ''}
            />
            {error && (
              <p className="text-xs text-destructive mt-1">{error}</p>
            )}
            <p className="text-xs text-muted-foreground mt-1">
              Supports GitHub, GitLab, Bitbucket, and other Git hosting services
            </p>
          </div>
          <div>
            <Label htmlFor="destination">Destination Folder (optional)</Label>
            <Input
              id="destination"
              value={destination}
              onChange={(e) => setDestination(e.target.value)}
              placeholder="Repository name"
              onKeyDown={(e) => {
                if (e.key === 'Enter' && !isCloning) handleConfirm();
              }}
              disabled={isCloning}
            />
            <p className="text-xs text-muted-foreground mt-1">
              Leave empty to use repository name
            </p>
          </div>
        </div>
        <DialogFooter>
          <Button 
            variant="outline" 
            onClick={() => {
              onOpenChange(false);
              setUrl('');
              setDestination('');
              setError('');
            }}
            disabled={isCloning}
          >
            Cancel
          </Button>
          <Button 
            onClick={handleConfirm} 
            disabled={!url.trim() || isCloning}
          >
            {isCloning ? (
              <>
                <Loader2 className="w-4 h-4 mr-2 animate-spin" />
                Cloning...
              </>
            ) : (
              <>
                <GitBranch className="w-4 h-4 mr-2" />
                Clone
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};

