import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';

const shortcuts = [
  { category: 'File', items: [
    { key: 'Ctrl+N', desc: 'New File' },
    { key: 'Ctrl+O', desc: 'Open File' },
    { key: 'Ctrl+S', desc: 'Save' },
    { key: 'Ctrl+Shift+S', desc: 'Save As' },
    { key: 'Ctrl+K S', desc: 'Save All' },
    { key: 'Ctrl+W', desc: 'Close Tab' },
  ]},
  { category: 'Edit', items: [
    { key: 'Ctrl+Z', desc: 'Undo' },
    { key: 'Ctrl+Y', desc: 'Redo' },
    { key: 'Ctrl+X', desc: 'Cut' },
    { key: 'Ctrl+C', desc: 'Copy' },
    { key: 'Ctrl+V', desc: 'Paste' },
    { key: 'Ctrl+F', desc: 'Find' },
    { key: 'Ctrl+H', desc: 'Replace' },
  ]},
  { category: 'View', items: [
    { key: 'Ctrl+Shift+P', desc: 'Command Palette' },
    { key: 'Ctrl+B', desc: 'Toggle Sidebar' },
    { key: 'Ctrl+Shift+E', desc: 'Explorer' },
    { key: 'Ctrl+Shift+F', desc: 'Search' },
    { key: 'Ctrl+Shift+G', desc: 'Source Control' },
    { key: 'Ctrl+`', desc: 'Toggle Terminal' },
    { key: 'Ctrl+Shift+V', desc: 'Toggle Preview' },
  ]},
  { category: 'Run', items: [
    { key: 'F5', desc: 'Start Debugging' },
    { key: 'Ctrl+F5', desc: 'Run Without Debugging' },
    { key: 'Shift+F5', desc: 'Stop Debugging' },
  ]},
  { category: 'Terminal', items: [
    { key: 'Ctrl+Shift+`', desc: 'New Terminal' },
    { key: 'Ctrl+Shift+B', desc: 'Run Build Task' },
  ]},
];

export const KeyboardShortcutsDialog: React.FC<{ open: boolean; onOpenChange: (open: boolean) => void }> = ({ open, onOpenChange }) => {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto hide-scrollbar">
        <DialogHeader>
          <DialogTitle>Keyboard Shortcuts</DialogTitle>
        </DialogHeader>
        <div className="space-y-6 py-4">
          {shortcuts.map((category) => (
            <div key={category.category}>
              <h3 className="text-sm font-semibold mb-2">{category.category}</h3>
              <div className="space-y-1">
                {category.items.map((item) => (
                  <div key={item.key} className="flex items-center justify-between py-1">
                    <span className="text-sm text-muted-foreground">{item.desc}</span>
                    <kbd className="px-2 py-1 text-xs bg-muted rounded">{item.key}</kbd>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </DialogContent>
    </Dialog>
  );
};


