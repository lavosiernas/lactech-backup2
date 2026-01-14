import { useState, useEffect } from 'react';
import { Puzzle, Download, Star, Check, X, Loader2, RefreshCw, Search } from 'lucide-react';
import { useToast } from '@/components/ui/use-toast';
import { ConfirmDialog } from './ConfirmDialog';
import { ExtensionDetailsDialog } from './ExtensionDetailsDialog';
import {
  searchExtensions,
  getPopularExtensions,
  installExtension,
  uninstallExtension,
  getInstalledExtensions,
  toggleExtension,
  type VSXExtension,
  type InstalledExtension,
} from '@/services/extensionService';

export const ExtensionsPanel: React.FC = () => {
  const [marketplaceExtensions, setMarketplaceExtensions] = useState<VSXExtension[]>([]);
  const [installedExtensions, setInstalledExtensions] = useState<InstalledExtension[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isSearching, setIsSearching] = useState(false);
  const { toast } = useToast();
  const [uninstallConfirm, setUninstallConfirm] = useState<{ open: boolean; id: string; name: string }>({ open: false, id: '', name: '' });
  const [selectedExtension, setSelectedExtension] = useState<VSXExtension | InstalledExtension | null>(null);
  const [detailsOpen, setDetailsOpen] = useState(false);

  // Carregar extensões instaladas e populares ao montar
  useEffect(() => {
    loadInstalledExtensions();
    loadPopularExtensions();
    
    // Ativar extensões instaladas que estão habilitadas
    const installed = getInstalledExtensions();
    installed.filter(ext => ext.enabled).forEach(ext => {
      // As extensões são ativadas automaticamente quando instaladas
      // Este é apenas um log para debug
      console.log(`[ExtensionsPanel] Extension ${ext.id} is enabled`);
    });
  }, []);

  const loadInstalledExtensions = () => {
    const installed = getInstalledExtensions();
    setInstalledExtensions(installed);
  };

  const loadPopularExtensions = async () => {
    setIsLoading(true);
    try {
      const popular = await getPopularExtensions(30);
      setMarketplaceExtensions(popular);
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to load extensions from marketplace',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleSearch = async (query: string) => {
    if (!query.trim()) {
      loadPopularExtensions();
      return;
    }

    setIsSearching(true);
    try {
      const results = await searchExtensions(query, 50);
      setMarketplaceExtensions(results);
      if (results.length === 0) {
        // Não mostrar toast para resultados vazios, apenas mostrar mensagem na UI
      }
    } catch (error) {
      console.error('Search error:', error);
      toast({
        title: 'Search failed',
        description: 'Failed to search extensions. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsSearching(false);
    }
  };

  // Debounce para busca
  useEffect(() => {
    if (!searchQuery.trim()) {
      loadPopularExtensions();
      return;
    }

    const timeoutId = setTimeout(() => {
      handleSearch(searchQuery);
    }, 500);

    return () => clearTimeout(timeoutId);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [searchQuery]);

  // Debounce para busca (removido - já está no onChange)

  const handleInstall = async (extension: VSXExtension) => {
    setIsLoading(true);
    try {
      const result = await installExtension(extension);
      if (result.success) {
        loadInstalledExtensions();
        toast({
          title: 'Extension installed',
          description: `${extension.displayName || extension.name || 'Extension'} installed successfully`,
        });
      } else {
        toast({
          title: 'Installation failed',
          description: result.error || 'Failed to install extension',
          variant: 'destructive',
        });
      }
    } catch (error) {
      toast({
        title: 'Error',
        description: 'An error occurred while installing the extension',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const handleUninstall = (extension: InstalledExtension) => {
    setUninstallConfirm({ 
      open: true, 
      id: extension.id, 
      name: extension.displayName 
    });
  };

  const confirmUninstall = async () => {
    setIsLoading(true);
    try {
      const result = await uninstallExtension(uninstallConfirm.id);
      if (result.success) {
        loadInstalledExtensions();
        toast({
          title: 'Extension uninstalled',
          description: `${uninstallConfirm.name} uninstalled successfully`,
        });
      } else {
        toast({
          title: 'Uninstall failed',
          description: result.error || 'Failed to uninstall extension',
          variant: 'destructive',
        });
      }
    } catch (error) {
      toast({
        title: 'Error',
        description: 'An error occurred while uninstalling the extension',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
      setUninstallConfirm({ open: false, id: '', name: '' });
    }
  };

  const handleToggle = async (extension: InstalledExtension) => {
    try {
      const result = await toggleExtension(extension.id);
      if (result.success) {
        loadInstalledExtensions();
        toast({
          title: extension.enabled ? 'Extension disabled' : 'Extension enabled',
          description: `${extension.displayName} ${extension.enabled ? 'disabled' : 'enabled'} successfully`,
        });
      }
    } catch (error) {
      toast({
        title: 'Error',
        description: 'Failed to toggle extension',
        variant: 'destructive',
      });
    }
  };

  // Filtrar extensões do marketplace que não estão instaladas
  const getMarketplaceOnly = () => {
    const installedIds = new Set(installedExtensions.map(ext => `${ext.namespace}.${ext.name}`));
    return marketplaceExtensions.filter(ext => {
      if (!ext || !ext.namespace || !ext.name) return false;
      return !installedIds.has(`${ext.namespace}.${ext.name}`);
    });
  };

  const formatDownloadCount = (count: number): string => {
    if (count >= 1000000) {
      return `${(count / 1000000).toFixed(1)}M`;
    }
    if (count >= 1000) {
      return `${(count / 1000).toFixed(1)}K`;
    }
    return count.toString();
  };

  // Tentar diferentes URLs para o ícone
  const getIconUrl = (ext: VSXExtension | InstalledExtension): string | null => {
    // Para VSXExtension
    if ('iconUrl' in ext && ext.iconUrl) return ext.iconUrl;
    if ('files' in ext && ext.files?.icon) return ext.files.icon;
    if ('metadata' in ext && ext.metadata?.icon) return ext.metadata.icon;
    // Para InstalledExtension
    if ('iconUrl' in ext && ext.iconUrl) return ext.iconUrl;
    // Tentar construir URL do Open VSX
    if ('namespace' in ext && ext.namespace && ext.name) {
      return `https://open-vsx.org/api/${ext.namespace}/${ext.name}/file/icon`;
    }
    return null;
  };

  return (
    <div className="h-full flex flex-col">
      <div className="flex items-center justify-between px-3 py-2">
        <span className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          Extensions
        </span>
      </div>
      
      <div className="p-2 space-y-2">
        <div className="flex items-center gap-2">
          <div className="flex-1 relative">
            <Search className="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => {
                setSearchQuery(e.target.value);
              }}
              placeholder="Search extensions in marketplace..."
              className="w-full pl-8 pr-2 py-1.5 text-sm bg-input border border-border rounded focus:outline-none focus:border-primary"
            />
          </div>
          <button
            onClick={loadPopularExtensions}
            className="p-1.5 rounded transition-colors"
            style={{ 
              color: 'rgba(255, 255, 255, 0.5)',
              backgroundColor: 'transparent'
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.backgroundColor = 'rgba(255, 255, 255, 0.08)';
              e.currentTarget.style.color = 'rgba(255, 255, 255, 0.8)';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.backgroundColor = 'transparent';
              e.currentTarget.style.color = 'rgba(255, 255, 255, 0.5)';
            }}
            title="Refresh"
          >
            <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin' : ''}`} />
          </button>
        </div>
      </div>

      <div className="flex-1 overflow-auto hide-scrollbar">
        {isLoading && marketplaceExtensions.length === 0 ? (
          <div className="flex items-center justify-center py-8">
            <Loader2 className="w-6 h-6 animate-spin text-muted-foreground" />
          </div>
        ) : (
          <>
            {/* Installed Extensions */}
            {installedExtensions.length > 0 && (
              <>
                <div className="px-2 py-1 text-xs text-muted-foreground uppercase">
                  Installed ({installedExtensions.length})
                </div>
                {installedExtensions.map((ext) => {
                  const iconUrl = getIconUrl(ext);
                  return (
                    <div 
                      key={ext.id} 
                      className="px-2 py-2 hover:bg-sidebar-hover group cursor-pointer"
                      onClick={() => {
                        setSelectedExtension(ext);
                        setDetailsOpen(true);
                      }}
                    >
                      <div className="flex items-start gap-2">
                        {iconUrl ? (
                          <img 
                            src={iconUrl} 
                            alt={ext.displayName}
                            className="w-10 h-10 rounded flex-shrink-0 object-cover"
                            onError={(e) => {
                              e.currentTarget.style.display = 'none';
                              const next = e.currentTarget.nextElementSibling as HTMLElement;
                              if (next) next.classList.remove('hidden');
                            }}
                          />
                        ) : null}
                        <div className={`w-10 h-10 rounded bg-gradient-to-br from-primary to-purple-500 flex items-center justify-center flex-shrink-0 ${iconUrl ? 'hidden' : ''}`}>
                          <Puzzle className="w-5 h-5 text-white" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2">
                            <span className="font-medium text-sm truncate">{ext.displayName}</span>
                            {ext.enabled && <Check className="w-3.5 h-3.5 text-success" />}
                          </div>
                          <div className="text-xs text-muted-foreground">{ext.publisher}</div>
                          <div className="text-xs text-muted-foreground truncate mt-0.5">
                            {ext.description}
                          </div>
                          <div className="text-[10px] text-muted-foreground mt-1">
                            v{ext.version}
                          </div>
                        </div>
                      <div className="flex items-center gap-1">
                        <button
                          onClick={() => handleToggle(ext)}
                          className="opacity-0 group-hover:opacity-100 px-2 py-1 text-xs rounded transition-opacity"
                          style={{
                            backgroundColor: ext.enabled ? 'rgba(239, 68, 68, 0.15)' : 'rgba(59, 130, 246, 0.15)',
                            color: ext.enabled ? 'rgba(239, 68, 68, 0.9)' : 'rgba(59, 130, 246, 0.9)',
                            border: `1px solid ${ext.enabled ? 'rgba(239, 68, 68, 0.3)' : 'rgba(59, 130, 246, 0.3)'}`
                          }}
                          title={ext.enabled ? 'Disable' : 'Enable'}
                        >
                          {ext.enabled ? 'Disable' : 'Enable'}
                        </button>
                        <button
                          onClick={() => handleUninstall(ext)}
                          className="opacity-0 group-hover:opacity-100 p-1 hover:bg-muted rounded transition-opacity"
                          title="Uninstall"
                        >
                          <X className="w-3.5 h-3.5" />
                        </button>
                      </div>
                    </div>
                  </div>
                  );
                })}
              </>
            )}

            {/* Marketplace Extensions */}
            {getMarketplaceOnly().length > 0 && (
              <>
                <div className="px-2 py-1 mt-2 text-xs text-muted-foreground uppercase pt-3">
                  {searchQuery ? 'Search Results' : 'Popular Extensions'} ({getMarketplaceOnly().length})
                </div>
                {getMarketplaceOnly().map((ext) => {
                  if (!ext || !ext.namespace || !ext.name) return null;
                  const extensionId = `${ext.namespace}.${ext.name}`;
                  const iconUrl = getIconUrl(ext);
                  return (
                    <div 
                      key={extensionId} 
                      className="px-2 py-2 hover:bg-sidebar-hover group cursor-pointer"
                      onClick={() => {
                        setSelectedExtension(ext);
                        setDetailsOpen(true);
                      }}
                    >
                      <div className="flex items-start gap-2">
                        {iconUrl ? (
                          <img 
                            src={iconUrl} 
                            alt={ext.displayName || ext.name || 'Extension'}
                            className="w-10 h-10 rounded flex-shrink-0 object-cover"
                            onError={(e) => {
                              e.currentTarget.style.display = 'none';
                              const next = e.currentTarget.nextElementSibling as HTMLElement;
                              if (next) next.classList.remove('hidden');
                            }}
                          />
                        ) : null}
                        <div className={`w-10 h-10 rounded bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center flex-shrink-0 ${iconUrl ? 'hidden' : ''}`}>
                          <Puzzle className="w-5 h-5 text-white" />
                        </div>
                        <div className="flex-1 min-w-0">
                          <div className="flex items-center gap-2">
                            <span className="font-medium text-sm truncate">
                              {ext.displayName || ext.name || 'Unknown Extension'}
                            </span>
                          </div>
                          <div className="text-xs text-muted-foreground">
                            {ext.publisher?.displayName || ext.publisher?.loginName || 'Unknown Publisher'}
                          </div>
                          <div className="text-xs text-muted-foreground truncate mt-0.5">
                            {ext.description || 'No description available'}
                          </div>
                          <div className="flex items-center gap-3 mt-1 text-xs text-muted-foreground">
                            <div className="flex items-center gap-1">
                              <Download className="w-3 h-3" />
                              <span>{formatDownloadCount(ext.downloadCount || 0)}</span>
                            </div>
                            {ext.averageRating && (
                              <div className="flex items-center gap-1">
                                <Star className="w-3 h-3 text-warning fill-warning" />
                                <span>{ext.averageRating.toFixed(1)}</span>
                                {ext.reviewCount && (
                                  <span className="text-[10px]">({ext.reviewCount})</span>
                                )}
                              </div>
                            )}
                            <span className="text-[10px]">v{ext.version || '1.0.0'}</span>
                          </div>
                        </div>
                        <button
                          onClick={() => handleInstall(ext)}
                          disabled={isLoading}
                          className="opacity-0 group-hover:opacity-100 px-3 py-1.5 text-xs bg-primary text-primary-foreground rounded hover:opacity-90 transition-opacity disabled:opacity-50 disabled:cursor-not-allowed"
                          title="Install"
                        >
                          {isLoading ? (
                            <Loader2 className="w-3 h-3 animate-spin" />
                          ) : (
                            'Install'
                          )}
                        </button>
                      </div>
                    </div>
                  );
                })}
              </>
            )}

            {!isLoading && installedExtensions.length === 0 && getMarketplaceOnly().length === 0 && (
              <div className="px-2 py-8 text-center text-sm text-muted-foreground">
                {isSearching ? (
                  <div className="flex flex-col items-center gap-2">
                    <Loader2 className="w-6 h-6 animate-spin" />
                    <p>Searching extensions...</p>
                  </div>
                ) : searchQuery ? (
                  <p>No extensions found for "{searchQuery}"</p>
                ) : (
                  <p>No extensions available</p>
                )}
              </div>
            )}
          </>
        )}
      </div>

      <ConfirmDialog
        open={uninstallConfirm.open}
        onOpenChange={(open) => setUninstallConfirm({ ...uninstallConfirm, open })}
        onConfirm={confirmUninstall}
        title="Uninstall Extension"
        message={`Are you sure you want to uninstall "${uninstallConfirm.name}"?`}
        confirmText="Uninstall"
        variant="destructive"
      />

      <ExtensionDetailsDialog
        open={detailsOpen}
        onOpenChange={setDetailsOpen}
        extension={selectedExtension}
        onInstall={handleInstall}
        onUninstall={handleUninstall}
        isInstalled={selectedExtension ? installedExtensions.some(ext => 
          'id' in selectedExtension && ext.id === selectedExtension.id
        ) : false}
      />
    </div>
  );
};
