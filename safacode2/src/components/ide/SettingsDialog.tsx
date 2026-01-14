import { useState } from 'react';
import { X } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export const SettingsDialog: React.FC<{ open: boolean; onOpenChange: (open: boolean) => void }> = ({ open, onOpenChange }) => {
  const { settings, updateSettings } = useIDEStore();
  const [localSettings, setLocalSettings] = useState({
    ...settings,
    syntaxColors: settings.syntaxColors || {
      comment: '6b7280',
      keyword: '60a5fa',
      string: '4ade80',
      number: 'fb923c',
      type: 'fbbf24',
      function: '60a5fa',
      variable: '38bdf8',
      operator: 'f472b6',
    }
  });

  const handleSave = () => {
    updateSettings(localSettings);
    onOpenChange(false);
  };

  const handleReset = () => {
    setLocalSettings({
      fontSize: 14,
      tabSize: 2,
      theme: 'dark',
      autoSave: false,
      wordWrap: true,
      minimap: true,
      syntaxColors: {
        comment: '6b7280',
        keyword: '60a5fa',
        string: '4ade80',
        number: 'fb923c',
        type: 'fbbf24',
        function: '60a5fa',
        variable: '38bdf8',
        operator: 'f472b6',
      }
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
        <DialogHeader>
          <DialogTitle>Settings</DialogTitle>
        </DialogHeader>
        
        <div className="space-y-6 py-4">
          {/* Editor Settings */}
          <div>
            <h3 className="text-sm font-semibold mb-3">Editor</h3>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <Label htmlFor="fontSize">Font Size</Label>
                <Input
                  id="fontSize"
                  type="number"
                  min="10"
                  max="30"
                  value={localSettings.fontSize}
                  onChange={(e) => setLocalSettings({ ...localSettings, fontSize: parseInt(e.target.value) || 14 })}
                  className="w-20"
                />
              </div>
              
              <div className="flex items-center justify-between">
                <Label htmlFor="tabSize">Tab Size</Label>
                <Select
                  value={localSettings.tabSize.toString()}
                  onValueChange={(value) => setLocalSettings({ ...localSettings, tabSize: parseInt(value) })}
                >
                  <SelectTrigger className="w-20">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="2">2</SelectItem>
                    <SelectItem value="4">4</SelectItem>
                    <SelectItem value="8">8</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="flex items-center justify-between">
                <Label htmlFor="wordWrap">Word Wrap</Label>
                <Switch
                  id="wordWrap"
                  checked={localSettings.wordWrap}
                  onCheckedChange={(checked) => setLocalSettings({ ...localSettings, wordWrap: checked })}
                />
              </div>

              <div className="flex items-center justify-between">
                <Label htmlFor="minimap">Minimap</Label>
                <Switch
                  id="minimap"
                  checked={localSettings.minimap}
                  onCheckedChange={(checked) => setLocalSettings({ ...localSettings, minimap: checked })}
                />
              </div>
            </div>
          </div>

          {/* Files Settings */}
          <div>
            <h3 className="text-sm font-semibold mb-3">Files</h3>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <Label htmlFor="autoSave">Auto Save</Label>
                <Switch
                  id="autoSave"
                  checked={localSettings.autoSave}
                  onCheckedChange={(checked) => setLocalSettings({ ...localSettings, autoSave: checked })}
                />
              </div>
            </div>
          </div>

          {/* Theme Settings */}
          <div>
            <h3 className="text-sm font-semibold mb-3">Appearance</h3>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <Label htmlFor="theme">Theme</Label>
                <Select
                  value={localSettings.theme}
                  onValueChange={(value: 'dark' | 'light') => setLocalSettings({ ...localSettings, theme: value })}
                >
                  <SelectTrigger className="w-32">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="dark">Dark</SelectItem>
                    <SelectItem value="light">Light</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>

          {/* Syntax Colors */}
          <div>
            <h3 className="text-sm font-semibold mb-3">Syntax Highlighting Colors</h3>
            <div className="space-y-3">
              {(['comment', 'keyword', 'string', 'number', 'type', 'function', 'variable', 'operator'] as const).map((colorKey) => (
                <div key={colorKey} className="flex items-center justify-between">
                  <Label htmlFor={colorKey} className="capitalize">{colorKey}</Label>
                  <div className="flex items-center gap-2">
                    <Input
                      id={colorKey}
                      type="color"
                      value={`#${localSettings.syntaxColors?.[colorKey] || 'ffffff'}`}
                      onChange={(e) => {
                        const color = e.target.value.replace('#', '');
                        setLocalSettings({
                          ...localSettings,
                          syntaxColors: {
                            ...localSettings.syntaxColors,
                            [colorKey]: color
                          }
                        });
                      }}
                      className="w-16 h-8 cursor-pointer"
                    />
                    <Input
                      type="text"
                      value={localSettings.syntaxColors?.[colorKey] || 'ffffff'}
                      onChange={(e) => {
                        const color = e.target.value.replace('#', '');
                        setLocalSettings({
                          ...localSettings,
                          syntaxColors: {
                            ...localSettings.syntaxColors,
                            [colorKey]: color
                          }
                        });
                      }}
                      className="w-20 text-xs font-mono"
                      placeholder="ffffff"
                    />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="flex justify-end gap-2 pt-4 border-t">
          <Button variant="outline" onClick={handleReset}>
            Reset Defaults
          </Button>
          <Button onClick={handleSave}>
            Save Changes
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

