import React, { useState, useEffect } from 'react';
import { X, Download, Star, ExternalLink, Package, Calendar, User, FileText, Image as ImageIcon } from 'lucide-react';
import { marked } from 'marked';
import { MarkdownRenderer } from './MarkdownRenderer';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { VSXExtension, InstalledExtension } from '@/services/extensionService';
import { getExtensionDetails } from '@/services/extensionService';

interface ExtensionDetailsDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  extension: VSXExtension | InstalledExtension | null;
  onInstall?: (extension: VSXExtension) => void;
  onUninstall?: (extension: InstalledExtension) => void;
  isInstalled?: boolean;
}

export const ExtensionDetailsDialog: React.FC<ExtensionDetailsDialogProps> = ({
  open,
  onOpenChange,
  extension,
  onInstall,
  onUninstall,
  isInstalled = false,
}) => {
  const [fullDetails, setFullDetails] = useState<VSXExtension | null>(null);
  const [readme, setReadme] = useState<string>('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (open && extension && 'namespace' in extension) {
      loadFullDetails(extension.namespace, extension.name);
    }
  }, [open, extension]);

  const loadFullDetails = async (namespace: string, name: string) => {
    setLoading(true);
    try {
      const details = await getExtensionDetails(namespace, name);
      if (details) {
        setFullDetails(details);
        
        // Tentar carregar README
        if (details.files?.readme) {
          try {
            const readmeResponse = await fetch(details.files.readme);
            if (readmeResponse.ok) {
              const readmeText = await readmeResponse.text();
              setReadme(readmeText);
            }
          } catch (error) {
            console.warn('Could not load README:', error);
          }
        }
      }
    } catch (error) {
      console.error('Error loading extension details:', error);
    } finally {
      setLoading(false);
    }
  };

  if (!extension) return null;

  const ext = fullDetails || extension;
  const displayName = ext.displayName || ext.name || 'Unknown Extension';
  const publisher = 'publisher' in ext && typeof ext.publisher === 'object' 
    ? (ext.publisher.displayName || ext.publisher.loginName || 'Unknown')
    : ('publisher' in ext ? ext.publisher : 'Unknown');
  
  const formatDownloadCount = (count: number): string => {
    if (count >= 1000000) return `${(count / 1000000).toFixed(1)}M`;
    if (count >= 1000) return `${(count / 1000).toFixed(1)}K`;
    return count.toString();
  };

  // Tentar diferentes URLs para o ícone
  const getIconUrl = () => {
    if (ext.iconUrl) return ext.iconUrl;
    if ('files' in ext && ext.files?.icon) return ext.files.icon;
    if ('metadata' in ext && ext.metadata?.icon) return ext.metadata.icon;
    return null;
  };

  const iconUrl = getIconUrl();

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden flex flex-col" style={{ backgroundColor: '#000000' }}>
        <DialogHeader className="flex-shrink-0 pb-4 border-b" style={{ borderColor: 'hsl(var(--panel-border))' }}>
          <div className="flex items-start gap-4">
            {iconUrl ? (
              <img 
                src={iconUrl}
                alt={displayName}
                className="w-16 h-16 rounded flex-shrink-0 object-cover"
                onError={(e) => {
                  e.currentTarget.style.display = 'none';
                  const fallback = e.currentTarget.nextElementSibling as HTMLElement;
                  if (fallback) fallback.classList.remove('hidden');
                }}
              />
            ) : null}
            <div className={`w-16 h-16 rounded bg-gradient-to-br from-primary to-purple-500 flex items-center justify-center flex-shrink-0 ${iconUrl ? 'hidden' : ''}`}>
              <Package className="w-8 h-8 text-white" />
            </div>
            <div className="flex-1 min-w-0">
              <DialogTitle className="text-xl font-semibold mb-1">{displayName}</DialogTitle>
              <DialogDescription className="text-sm text-muted-foreground">
                {ext.description || 'No description available'}
              </DialogDescription>
              <div className="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                <div className="flex items-center gap-1">
                  <User className="w-3 h-3" />
                  <span>{publisher}</span>
                </div>
                {'downloadCount' in ext && ext.downloadCount > 0 && (
                  <div className="flex items-center gap-1">
                    <Download className="w-3 h-3" />
                    <span>{formatDownloadCount(ext.downloadCount)}</span>
                  </div>
                )}
                {'averageRating' in ext && ext.averageRating && (
                  <div className="flex items-center gap-1">
                    <Star className="w-3 h-3 text-warning fill-warning" />
                    <span>{ext.averageRating.toFixed(1)}</span>
                    {'reviewCount' in ext && ext.reviewCount && (
                      <span className="ml-1">({ext.reviewCount})</span>
                    )}
                  </div>
                )}
                <div className="flex items-center gap-1">
                  <Package className="w-3 h-3" />
                  <span>v{ext.version || '1.0.0'}</span>
                </div>
              </div>
            </div>
          </div>
        </DialogHeader>

        <div className="flex-1 overflow-y-auto hide-scrollbar py-4 space-y-6">
          {/* Informações adicionais */}
          {'metadata' in ext && ext.metadata && (
            <div className="space-y-4">
              {ext.metadata.homepage && (
                <div className="flex items-center gap-2 text-sm">
                  <ExternalLink className="w-4 h-4 text-muted-foreground" />
                  <a 
                    href={ext.metadata.homepage} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="text-primary hover:underline"
                  >
                    Homepage
                  </a>
                </div>
              )}
              {ext.metadata.repository && (
                <div className="flex items-center gap-2 text-sm">
                  <ExternalLink className="w-4 h-4 text-muted-foreground" />
                  <a 
                    href={ext.metadata.repository} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="text-primary hover:underline"
                  >
                    Repository
                  </a>
                </div>
              )}
              {ext.metadata.license && (
                <div className="flex items-center gap-2 text-sm">
                  <FileText className="w-4 h-4 text-muted-foreground" />
                  <span>License: {ext.metadata.license}</span>
                </div>
              )}
              {ext.metadata.categories && ext.metadata.categories.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {ext.metadata.categories.map((cat, idx) => (
                    <span 
                      key={idx}
                      className="px-2 py-1 text-xs rounded"
                      style={{ 
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        color: 'rgba(59, 130, 246, 0.9)',
                        border: '1px solid rgba(59, 130, 246, 0.3)'
                      }}
                    >
                      {cat}
                    </span>
                  ))}
                </div>
              )}
              {ext.metadata.tags && ext.metadata.tags.length > 0 && (
                <div className="flex flex-wrap gap-2">
                  {ext.metadata.tags.map((tag, idx) => (
                    <span 
                      key={idx}
                      className="px-2 py-1 text-xs rounded"
                      style={{ 
                        backgroundColor: 'rgba(255, 255, 255, 0.05)',
                        color: 'rgba(255, 255, 255, 0.7)',
                        border: '1px solid rgba(255, 255, 255, 0.1)'
                      }}
                    >
                      {tag}
                    </span>
                  ))}
                </div>
              )}
            </div>
          )}

          {/* README */}
          {readme && (
            <div className="space-y-3">
              <h3 className="text-sm font-semibold flex items-center gap-2">
                <FileText className="w-4 h-4" />
                README
              </h3>
              <div 
                className="rounded overflow-auto hide-scrollbar"
                style={{ 
                  backgroundColor: 'rgba(255, 255, 255, 0.02)',
                  border: '1px solid hsl(var(--panel-border))',
                  maxHeight: '500px',
                  padding: '1.5rem',
                }}
              >
                <MarkdownRenderer content={readme} />
              </div>
            </div>
          )}

          {loading && (
            <div className="flex items-center justify-center py-8">
              <div className="text-sm text-muted-foreground">Loading details...</div>
            </div>
          )}
        </div>

        <div className="flex-shrink-0 pt-4 border-t flex items-center justify-end gap-2" style={{ borderColor: 'hsl(var(--panel-border))' }}>
          {isInstalled && onUninstall && 'id' in extension && (
            <Button
              variant="destructive"
              onClick={() => {
                onUninstall(extension as InstalledExtension);
                onOpenChange(false);
              }}
            >
              Uninstall
            </Button>
          )}
          {!isInstalled && onInstall && 'namespace' in extension && (
            <Button
              onClick={() => {
                onInstall(extension as VSXExtension);
                onOpenChange(false);
              }}
            >
              <Download className="w-4 h-4 mr-2" />
              Install
            </Button>
          )}
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

