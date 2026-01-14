import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

export const AboutDialog: React.FC<{ open: boolean; onOpenChange: (open: boolean) => void }> = ({ open, onOpenChange }) => {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>About SAFECODE</DialogTitle>
        </DialogHeader>
        <div className="space-y-4 py-4">
          <div className="flex items-center gap-4">
            <img 
              src="/logos (6).png" 
              alt="SAFECODE" 
              className="w-16 h-16 object-contain"
              onError={(e) => {
                e.currentTarget.style.display = 'none';
              }}
            />
            <div>
              <h2 className="text-xl font-semibold">SAFECODE IDE</h2>
              <p className="text-sm text-muted-foreground">Version 1.0.0</p>
            </div>
          </div>
          <p className="text-sm text-muted-foreground">
            A modern, powerful code editor built with React, TypeScript, and Monaco Editor.
          </p>
          <div className="pt-4 border-t">
            <p className="text-xs text-muted-foreground">
              Built with React, TypeScript, Vite, Tailwind CSS, and shadcn-ui
            </p>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};


