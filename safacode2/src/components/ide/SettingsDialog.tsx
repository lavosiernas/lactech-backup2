import { useState, useEffect } from 'react';
import { X, Settings as SettingsIcon, Palette, ChevronRight, ChevronDown, Package } from 'lucide-react';
import { useIDEStore } from '@/stores/ideStore';
import { getAvailableThemes } from '@/services/extensionService';
import type { ExtensionTheme } from '@/services/extensionService';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
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
    },
    activeTheme: settings.activeTheme || 'default',
  });
  const [availableThemes, setAvailableThemes] = useState<ExtensionTheme[]>([]);
  const [expandedExtensions, setExpandedExtensions] = useState<Set<string>>(new Set());

  useEffect(() => {
    if (open) {
      const themes = getAvailableThemes();
      setAvailableThemes(themes);
    }
  }, [open]);

  // Agrupar temas por extensão
  const themesByExtension = availableThemes.reduce((acc, theme) => {
    if (!acc[theme.extensionId]) {
      acc[theme.extensionId] = {
        extensionId: theme.extensionId,
        extensionName: theme.extensionName,
        themes: [],
      };
    }
    acc[theme.extensionId].themes.push(theme);
    return acc;
  }, {} as Record<string, { extensionId: string; extensionName: string; themes: ExtensionTheme[] }>);

  const extensionList = Object.values(themesByExtension);

  const toggleExtension = (extensionId: string) => {
    setExpandedExtensions(prev => {
      const newSet = new Set(prev);
      if (newSet.has(extensionId)) {
        newSet.delete(extensionId);
      } else {
        newSet.add(extensionId);
      }
      return newSet;
    });
  };

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
      },
      activeTheme: 'default',
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent 
        className="max-w-none w-screen h-screen m-0 p-0 rounded-none border-0"
        style={{ backgroundColor: '#000000' }}
      >
        <div className="flex flex-col h-full">
          {/* Header */}
          <DialogHeader className="px-8 py-6 border-b" style={{ borderColor: 'hsl(var(--panel-border))' }}>
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <SettingsIcon className="w-5 h-5 text-foreground" />
                <div>
                  <DialogTitle className="text-xl font-semibold">Configurações</DialogTitle>
                  <DialogDescription className="sr-only">
                    Configure as preferências do editor e da IDE
                  </DialogDescription>
                </div>
              </div>
            </div>
          </DialogHeader>
          
          {/* Content */}
          <div className="flex-1 overflow-y-auto hide-scrollbar px-8 py-6">
            <div className="max-w-6xl mx-auto">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {/* Editor Settings */}
                <div className="space-y-6">
                  <div>
                    <h3 className="text-base font-semibold mb-4 pb-2 border-b" style={{ borderColor: 'hsl(var(--panel-border))' }}>
                      Editor
                    </h3>
                    <div className="space-y-5">
                      <div className="space-y-2">
                        <Label htmlFor="fontSize" className="text-sm">Tamanho da Fonte</Label>
                        <Input
                          id="fontSize"
                          type="number"
                          min="10"
                          max="30"
                          value={localSettings.fontSize}
                          onChange={(e) => setLocalSettings({ ...localSettings, fontSize: parseInt(e.target.value) || 14 })}
                          className="w-full max-w-xs"
                          style={{ backgroundColor: 'hsl(var(--input))' }}
                        />
                      </div>
                      
                      <div className="space-y-2">
                        <Label htmlFor="tabSize" className="text-sm">Tamanho da Tab</Label>
                        <Select
                          value={localSettings.tabSize.toString()}
                          onValueChange={(value) => setLocalSettings({ ...localSettings, tabSize: parseInt(value) })}
                        >
                          <SelectTrigger className="w-full max-w-xs" style={{ backgroundColor: 'hsl(var(--input))' }}>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="2">2</SelectItem>
                            <SelectItem value="4">4</SelectItem>
                            <SelectItem value="8">8</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>

                      <div className="flex items-center justify-between py-2">
                        <div className="space-y-0.5">
                          <Label htmlFor="wordWrap" className="text-sm">Quebra de Linha</Label>
                          <p className="text-xs text-muted-foreground">Quebrar linhas longas automaticamente</p>
                        </div>
                        <Switch
                          id="wordWrap"
                          checked={localSettings.wordWrap}
                          onCheckedChange={(checked) => setLocalSettings({ ...localSettings, wordWrap: checked })}
                        />
                      </div>

                      <div className="flex items-center justify-between py-2">
                        <div className="space-y-0.5">
                          <Label htmlFor="minimap" className="text-sm">Minimap</Label>
                          <p className="text-xs text-muted-foreground">Mostrar minimapa do código</p>
                        </div>
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
                    <h3 className="text-base font-semibold mb-4 pb-2 border-b" style={{ borderColor: 'hsl(var(--panel-border))' }}>
                      Arquivos
                    </h3>
                    <div className="space-y-5">
                      <div className="flex items-center justify-between py-2">
                        <div className="space-y-0.5">
                          <Label htmlFor="autoSave" className="text-sm">Salvamento Automático</Label>
                          <p className="text-xs text-muted-foreground">Salvar arquivos automaticamente</p>
                        </div>
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
                    <h3 className="text-base font-semibold mb-4 pb-2 border-b" style={{ borderColor: 'hsl(var(--panel-border))' }}>
                      Aparência
                    </h3>
                    <div className="space-y-5">
                      <div className="space-y-2">
                        <Label htmlFor="theme" className="text-sm">Tema Base</Label>
                        <Select
                          value={localSettings.theme}
                          onValueChange={(value: 'dark' | 'light') => setLocalSettings({ ...localSettings, theme: value })}
                        >
                          <SelectTrigger className="w-full max-w-xs" style={{ backgroundColor: 'hsl(var(--input))' }}>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="dark">Escuro</SelectItem>
                            <SelectItem value="light">Claro</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                    </div>
                  </div>

                  {/* Extension Themes */}
                  {extensionList.length > 0 && (
                    <div>
                      <h3 className="text-base font-semibold mb-4 pb-2 border-b flex items-center gap-2" style={{ borderColor: 'hsl(var(--panel-border))' }}>
                        <Palette className="w-4 h-4" />
                        Temas das Extensões
                      </h3>
                      <div className="space-y-2">
                        {extensionList.map((extension) => {
                          const isExpanded = expandedExtensions.has(extension.extensionId);
                          const activeTheme = extension.themes.find(t => t.id === localSettings.activeTheme);
                          
                          return (
                            <div 
                              key={extension.extensionId}
                              className="rounded-lg border"
                              style={{ 
                                borderColor: 'hsl(var(--panel-border))',
                                backgroundColor: activeTheme ? 'rgba(59, 130, 246, 0.05)' : 'transparent'
                              }}
                            >
                              <button
                                onClick={() => toggleExtension(extension.extensionId)}
                                className="w-full flex items-center justify-between p-3 hover:bg-muted/50 transition-colors rounded-t-lg"
                              >
                                <div className="flex items-center gap-3">
                                  {isExpanded ? (
                                    <ChevronDown className="w-4 h-4 text-muted-foreground" />
                                  ) : (
                                    <ChevronRight className="w-4 h-4 text-muted-foreground" />
                                  )}
                                  <Package className="w-4 h-4 text-muted-foreground" />
                                  <div className="text-left">
                                    <div className="text-sm font-medium">{extension.extensionName}</div>
                                    <div className="text-xs text-muted-foreground">
                                      {extension.themes.length} {extension.themes.length === 1 ? 'tema' : 'temas'}
                                    </div>
                                  </div>
                                </div>
                                {activeTheme && (
                                  <div className="text-xs px-2 py-1 rounded" style={{ 
                                    backgroundColor: 'rgba(59, 130, 246, 0.15)',
                                    color: 'rgba(59, 130, 246, 0.9)'
                                  }}>
                                    Ativo
                                  </div>
                                )}
                              </button>
                              
                              {isExpanded && (
                                <div className="px-3 pb-3 space-y-2 border-t" style={{ borderColor: 'hsl(var(--panel-border))' }}>
                                  {extension.themes.map((theme) => {
                                    const isActive = localSettings.activeTheme === theme.id;
                                    return (
                                      <button
                                        key={theme.id}
                                        onClick={() => setLocalSettings({ ...localSettings, activeTheme: theme.id })}
                                        className="w-full flex items-center justify-between p-2 rounded hover:bg-muted/30 transition-colors text-left"
                                        style={{
                                          backgroundColor: isActive ? 'rgba(59, 130, 246, 0.15)' : 'transparent',
                                          border: isActive ? '1px solid rgba(59, 130, 246, 0.3)' : '1px solid transparent',
                                        }}
                                      >
                                        <div className="flex items-center gap-2">
                                          <div 
                                            className="w-3 h-3 rounded-full"
                                            style={{
                                              backgroundColor: isActive ? '#3b82f6' : 'rgba(255, 255, 255, 0.2)',
                                              border: '1px solid rgba(255, 255, 255, 0.3)'
                                            }}
                                          />
                                          <span className="text-sm">{theme.label}</span>
                                        </div>
                                        {isActive && (
                                          <span className="text-xs text-muted-foreground">✓</span>
                                        )}
                                      </button>
                                    );
                                  })}
                                  {localSettings.activeTheme && localSettings.activeTheme.startsWith(extension.extensionId) && (
                                    <button
                                      onClick={() => setLocalSettings({ ...localSettings, activeTheme: 'default' })}
                                      className="w-full p-2 rounded hover:bg-muted/30 transition-colors text-left text-xs text-muted-foreground"
                                    >
                                      Remover tema
                                    </button>
                                  )}
                                </div>
                              )}
                            </div>
                          );
                        })}
                      </div>
                      
                      {localSettings.activeTheme && localSettings.activeTheme !== 'default' && (
                        <div className="mt-4 p-3 rounded-lg" style={{ backgroundColor: 'rgba(59, 130, 246, 0.1)', border: '1px solid rgba(59, 130, 246, 0.2)' }}>
                          <p className="text-xs text-muted-foreground">
                            Tema ativo: <span className="text-foreground font-medium">
                              {availableThemes.find(t => t.id === localSettings.activeTheme)?.label || 'Desconhecido'}
                            </span>
                          </p>
                          <p className="text-xs text-muted-foreground mt-1">
                            Da extensão: <span className="text-foreground">
                              {availableThemes.find(t => t.id === localSettings.activeTheme)?.extensionName || 'Desconhecida'}
                            </span>
                          </p>
                        </div>
                      )}
                    </div>
                  )}
                </div>

                {/* Syntax Colors */}
                <div className="space-y-6">
                  <div>
                    <h3 className="text-base font-semibold mb-4 pb-2 border-b" style={{ borderColor: 'hsl(var(--panel-border))' }}>
                      Cores de Sintaxe
                    </h3>
                    <div className="space-y-4">
                      {(['comment', 'keyword', 'string', 'number', 'type', 'function', 'variable', 'operator'] as const).map((colorKey) => (
                        <div key={colorKey} className="flex items-center gap-4 p-3 rounded-lg hover:bg-muted/50 transition-colors">
                          <div className="flex-1 min-w-0">
                            <Label htmlFor={colorKey} className="text-sm capitalize block mb-1">
                              {colorKey === 'comment' ? 'Comentário' :
                               colorKey === 'keyword' ? 'Palavra-chave' :
                               colorKey === 'string' ? 'String' :
                               colorKey === 'number' ? 'Número' :
                               colorKey === 'type' ? 'Tipo' :
                               colorKey === 'function' ? 'Função' :
                               colorKey === 'variable' ? 'Variável' :
                               'Operador'}
                            </Label>
                            <p className="text-xs text-muted-foreground">
                              Cor usada para destacar {colorKey === 'comment' ? 'comentários' :
                               colorKey === 'keyword' ? 'palavras-chave' :
                               colorKey === 'string' ? 'strings' :
                               colorKey === 'number' ? 'números' :
                               colorKey === 'type' ? 'tipos' :
                               colorKey === 'function' ? 'funções' :
                               colorKey === 'variable' ? 'variáveis' :
                               'operadores'} no código
                            </p>
                          </div>
                          <div className="flex items-center gap-2 flex-shrink-0">
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
                              className="w-12 h-12 cursor-pointer rounded border-2"
                              style={{ 
                                borderColor: 'hsl(var(--border))',
                                backgroundColor: 'transparent'
                              }}
                            />
                            <Input
                              type="text"
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
                              className="w-24 text-xs font-mono"
                              style={{ backgroundColor: 'hsl(var(--input))' }}
                              placeholder="#ffffff"
                            />
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Footer */}
          <div className="px-8 py-4 border-t flex justify-end gap-3" style={{ borderColor: 'hsl(var(--panel-border))' }}>
            <Button 
              variant="outline" 
              onClick={handleReset}
              className="min-w-[140px]"
            >
              Restaurar Padrões
            </Button>
            <Button 
              onClick={handleSave}
              className="min-w-[140px]"
            >
              Salvar Alterações
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
};

