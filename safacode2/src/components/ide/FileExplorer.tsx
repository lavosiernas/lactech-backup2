import { ChevronRight, ChevronDown, MoreHorizontal, Plus, FolderPlus, RefreshCw } from 'lucide-react';
import { useState } from 'react';
import { FileIcon } from './FileIcon';
import { useIDEStore } from '@/stores/ideStore';
import { ConfirmDialog } from './ConfirmDialog';
import { NewFileDialog } from './NewFileDialog';
import { useToast } from '@/components/ui/use-toast';
import type { FileNode } from '@/types/ide';
import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuTrigger,
} from '@/components/ui/context-menu';

interface FileTreeProps {
  nodes: FileNode[];
  depth?: number;
}

const FileTreeItem: React.FC<{ 
  node: FileNode; 
  depth: number;
  onRename?: (id: string, name: string) => void;
  onDelete?: (path: string) => void;
  onNewFile?: (parentPath: string) => void;
  onNewFolder?: (parentPath: string) => void;
}> = ({ node, depth, onRename, onDelete, onNewFile, onNewFolder }) => {
  const { toggleFolder, openFile, activeTabId, tabs } = useIDEStore();
  const [isRenaming, setIsRenaming] = useState(false);
  const [renameValue, setRenameValue] = useState(node.name);
  
  const isActive = tabs.find(t => t.id === activeTabId)?.path === node.path;
  
  const handleClick = () => {
    if (isRenaming) return;
    if (node.type === 'folder') {
      toggleFolder(node.path);
    } else {
      openFile(node);
    }
  };

  const handleRename = () => {
    if (renameValue.trim() && renameValue !== node.name) {
      onRename?.(node.path, renameValue.trim());
    }
    setIsRenaming(false);
    setRenameValue(node.name);
  };

  return (
    <ContextMenu>
      <ContextMenuTrigger>
        <div
          onClick={handleClick}
          className={`ide-sidebar-item group ${isActive ? 'active' : ''}`}
          style={{ paddingLeft: `${depth * 12 + 8}px` }}
        >
          {node.type === 'folder' && (
            <span className="w-4 h-4 flex items-center justify-center flex-shrink-0 transition-transform">
              {node.isExpanded ? (
                <ChevronDown className="w-3 h-3 transition-transform rotate-0" />
              ) : (
                <ChevronRight className="w-3 h-3 transition-transform rotate-0" />
              )}
            </span>
          )}
          {node.type === 'file' && <span className="w-4" />}
          <FileIcon file={node} />
          {isRenaming ? (
            <input
              type="text"
              value={renameValue}
              onChange={(e) => setRenameValue(e.target.value)}
              onBlur={handleRename}
              onKeyDown={(e) => {
                if (e.key === 'Enter') handleRename();
                if (e.key === 'Escape') {
                  setIsRenaming(false);
                  setRenameValue(node.name);
                }
              }}
              className="flex-1 px-1 py-0.5 text-xs bg-input border border-primary rounded outline-none animate-scale-in"
              autoFocus
              onClick={(e) => e.stopPropagation()}
            />
          ) : (
            <span className="truncate text-xs">{node.name}</span>
          )}
        </div>
      </ContextMenuTrigger>
      <ContextMenuContent className="border-border" style={{ backgroundColor: '#000000' }}>
        {node.type === 'folder' && (
          <>
            <ContextMenuItem 
              className="text-sm cursor-pointer"
              onClick={(e) => {
                e.stopPropagation();
                onNewFile?.(node.path, 'file');
              }}
            >
              <Plus className="w-4 h-4 mr-2" />
              New File
            </ContextMenuItem>
            <ContextMenuItem 
              className="text-sm cursor-pointer"
              onClick={(e) => {
                e.stopPropagation();
                onNewFolder?.(node.path, 'folder');
              }}
            >
              <FolderPlus className="w-4 h-4 mr-2" />
              New Folder
            </ContextMenuItem>
          </>
        )}
        <ContextMenuItem 
          className="text-sm cursor-pointer"
          onClick={(e) => {
            e.stopPropagation();
            setIsRenaming(true);
          }}
        >
          Rename
        </ContextMenuItem>
        <ContextMenuItem 
          className="text-sm cursor-pointer text-destructive"
          onClick={(e) => {
            e.stopPropagation();
            onDelete?.(node.path, node.name);
          }}
        >
          Delete
        </ContextMenuItem>
      </ContextMenuContent>
    </ContextMenu>
  );
};

const FileTreeRecursive: React.FC<FileTreeProps & {
  onRename?: (path: string, name: string) => void;
  onDelete?: (path: string, name: string) => void;
  onNewFile?: (parentPath: string, type: 'file' | 'folder') => void;
  onNewFolder?: (parentPath: string, type: 'file' | 'folder') => void;
}> = ({ nodes, depth = 0, onRename, onDelete, onNewFile, onNewFolder }) => {
  return (
    <>
      {nodes.map((node) => (
        <div key={node.id}>
          <FileTreeItem 
            node={node} 
            depth={depth}
            onRename={onRename}
            onDelete={onDelete}
            onNewFile={onNewFile}
            onNewFolder={onNewFolder}
          />
          {node.type === 'folder' && node.isExpanded && node.children && (
            <FileTreeRecursive 
              nodes={node.children} 
              depth={depth + 1}
              onRename={onRename}
              onDelete={onDelete}
              onNewFile={onNewFile}
              onNewFolder={onNewFolder}
            />
          )}
        </div>
      ))}
    </>
  );
};

export const FileExplorer: React.FC = () => {
  const { files, createFile, createFolder, deleteFile, renameFile } = useIDEStore();
  const { toast } = useToast();
  const [deleteConfirm, setDeleteConfirm] = useState<{ open: boolean; path: string; name: string }>({ open: false, path: '', name: '' });
  const [newFileDialog, setNewFileDialog] = useState<{ open: boolean; type: 'file' | 'folder'; parentPath: string }>({ open: false, type: 'file', parentPath: '/' });

  return (
    <div className="h-full flex flex-col">
      <div className="flex items-center justify-between px-2 py-1.5 border-b border-panel-border">
        <span className="text-[10px] font-medium uppercase tracking-wider text-muted-foreground">
          Explorer
        </span>
        <div className="flex items-center gap-0.5">
          <button 
            onClick={() => setNewFileDialog({ open: true, type: 'file', parentPath: '/' })}
            className="p-1 hover:bg-sidebar-hover rounded transition-colors"
            title="New File"
          >
            <Plus className="w-3 h-3 text-muted-foreground hover:text-foreground" />
          </button>
          <button 
            onClick={() => setNewFileDialog({ open: true, type: 'folder', parentPath: '/' })}
            className="p-1 hover:bg-sidebar-hover rounded transition-colors"
            title="New Folder"
          >
            <FolderPlus className="w-3 h-3 text-muted-foreground hover:text-foreground" />
          </button>
          <button 
            className="p-1 hover:bg-sidebar-hover rounded transition-colors"
            title="Refresh"
          >
            <RefreshCw className="w-3 h-3 text-muted-foreground hover:text-foreground" />
          </button>
        </div>
      </div>
      <div className="flex-1 overflow-auto scrollbar-thin py-1">
        <FileTreeRecursive 
          nodes={files}
          onRename={(path, name) => {
            renameFile(path, name);
            toast({
              title: 'File renamed',
              description: `Renamed to ${name}`,
            });
          }}
          onDelete={(path, name) => {
            setDeleteConfirm({ open: true, path, name });
          }}
          onNewFile={(parentPath, type) => {
            setNewFileDialog({ open: true, type, parentPath });
          }}
          onNewFolder={(parentPath, type) => {
            setNewFileDialog({ open: true, type, parentPath });
          }}
        />
      </div>

      {/* Dialogs */}
      <ConfirmDialog
        open={deleteConfirm.open}
        onOpenChange={(open) => setDeleteConfirm({ ...deleteConfirm, open })}
        onConfirm={() => {
          deleteFile(deleteConfirm.path);
          toast({
            title: 'Deleted',
            description: `${deleteConfirm.name} deleted successfully`,
          });
        }}
        title="Delete File"
        message={`Are you sure you want to delete "${deleteConfirm.name}"?`}
        confirmText="Delete"
        variant="destructive"
      />
      <NewFileDialog
        open={newFileDialog.open}
        onOpenChange={(open) => setNewFileDialog({ ...newFileDialog, open })}
        onConfirm={(name) => {
          if (newFileDialog.type === 'file') {
            createFile(newFileDialog.parentPath, name);
          } else {
            createFolder(newFileDialog.parentPath, name);
          }
          toast({
            title: `${newFileDialog.type === 'file' ? 'File' : 'Folder'} created`,
            description: `${name} created successfully`,
          });
        }}
        type={newFileDialog.type}
        parentPath={newFileDialog.parentPath}
      />
    </div>
  );
};
