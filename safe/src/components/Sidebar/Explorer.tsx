import React, { useState, useEffect } from 'react';
import { useIDE } from '../../contexts/IDEContext';
import { FileItem } from '../../types';
import './Explorer.css';

const Explorer: React.FC = () => {
  const { state, openFile, isElectron } = useIDE();
  const [files, setFiles] = useState<FileItem[]>([]);
  const [expandedFolders, setExpandedFolders] = useState<Set<string>>(new Set());

  useEffect(() => {
    if (state.workspace && isElectron) {
      loadFiles(state.workspace);
    }
  }, [state.workspace, isElectron]);

  const loadFiles = async (path: string) => {
    try {
      if ((window as any).electronAPI?.fs) {
        const result = await (window as any).electronAPI.fs.readDir(path);
        if (result.success) {
          setFiles(result.items || []);
        }
      }
    } catch (error) {
      console.error('Error loading files:', error);
    }
  };

  const toggleFolder = (path: string) => {
    setExpandedFolders(prev => {
      const newSet = new Set(prev);
      if (newSet.has(path)) {
        newSet.delete(path);
      } else {
        newSet.add(path);
      }
      return newSet;
    });
  };

  const handleFileClick = async (filePath: string) => {
    try {
      if ((window as any).electronAPI?.fs) {
        const result = await (window as any).electronAPI.fs.readFile(filePath);
        if (result.success) {
          openFile(filePath, result.content);
        }
      }
    } catch (error) {
      console.error('Error opening file:', error);
    }
  };

  const renderFileTree = (items: FileItem[], level: number = 0): React.ReactNode => {
    return items.map(item => {
      const isExpanded = expandedFolders.has(item.path);
      const indent = level * 16;

      if (item.isDirectory) {
        return (
          <div key={item.path}>
            <div
              className="tree-item tree-folder"
              style={{ paddingLeft: `${indent + 12}px` }}
              onClick={() => toggleFolder(item.path)}
            >
              <span className="tree-chevron">{isExpanded ? '▼' : '▶'}</span>
              <span className="tree-icon">📁</span>
              <span className="tree-label">{item.name}</span>
            </div>
            {isExpanded && item.children && (
              <div>{renderFileTree(item.children, level + 1)}</div>
            )}
          </div>
        );
      }

      return (
        <div
          key={item.path}
          className="tree-item tree-file"
          style={{ paddingLeft: `${indent + 12}px` }}
          onClick={() => handleFileClick(item.path)}
        >
          <span className="tree-spacer"></span>
          <span className="tree-icon">📄</span>
          <span className="tree-label">{item.name}</span>
        </div>
      );
    });
  };

  if (!state.workspace) {
    return (
      <div className="sidebar-content">
        <div className="sidebar-section">
          <div className="section-header">
            <span>EXPLORER</span>
          </div>
          <div className="empty-state">
            <p>No folder opened</p>
            <button className="btn-primary-sm">Open Folder</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="sidebar-content">
      <div className="sidebar-section">
        <div className="section-header">
          <span>EXPLORER</span>
        </div>
        <div className="file-tree">
          {files.length > 0 ? renderFileTree(files) : <div className="loading">Loading...</div>}
        </div>
      </div>
    </div>
  );
};

export default Explorer;



