import React, { createContext, useContext, useState, useCallback, ReactNode } from 'react';
import { IDEState, EditorTab, FileItem } from '../types';

interface IDEContextType {
  state: IDEState;
  setWorkspace: (path: string | null) => void;
  openFile: (filePath: string, content: string) => void;
  closeTab: (tabId: string) => void;
  setActiveTab: (tabId: string | null) => void;
  updateTabContent: (tabId: string, content: string) => void;
  toggleSidebar: () => void;
  toggleTerminal: () => void;
  togglePreview: () => void;
  setSidebarView: (view: IDEState['activeSidebarView']) => void;
  isElectron: boolean;
}

const IDEContext = createContext<IDEContextType | undefined>(undefined);

export const IDEProvider: React.FC<{ children: ReactNode }> = ({ children }) => {
  const isElectron = typeof window !== 'undefined' && !!(window as any).electronAPI;

  const [state, setState] = useState<IDEState>({
    workspace: null,
    openTabs: [],
    activeTab: null,
    sidebarVisible: true,
    terminalVisible: false,
    previewVisible: false,
    activeSidebarView: 'explorer'
  });

  const setWorkspace = useCallback((path: string | null) => {
    setState(prev => ({ ...prev, workspace: path }));
  }, []);

  const openFile = useCallback((filePath: string, content: string) => {
    setState(prev => {
      const existingTab = prev.openTabs.find(tab => tab.filePath === filePath);
      if (existingTab) {
        return { ...prev, activeTab: existingTab.id };
      }

      const fileName = filePath.split(/[/\\]/).pop() || 'untitled';
      const extension = fileName.split('.').pop()?.toLowerCase();
      const languageMap: Record<string, string> = {
        'js': 'javascript',
        'jsx': 'javascript',
        'ts': 'typescript',
        'tsx': 'typescript',
        'html': 'html',
        'css': 'css',
        'json': 'json',
        'md': 'markdown',
        'py': 'python',
        'php': 'php'
      };

      const newTab: EditorTab = {
        id: `tab-${Date.now()}`,
        filePath,
        fileName,
        content,
        isDirty: false,
        language: languageMap[extension || '']
      };

      return {
        ...prev,
        openTabs: [...prev.openTabs, newTab],
        activeTab: newTab.id
      };
    });
  }, []);

  const closeTab = useCallback((tabId: string) => {
    setState(prev => {
      const newTabs = prev.openTabs.filter(tab => tab.id !== tabId);
      const newActiveTab = prev.activeTab === tabId
        ? (newTabs.length > 0 ? newTabs[newTabs.length - 1].id : null)
        : prev.activeTab;
      return { ...prev, openTabs: newTabs, activeTab: newActiveTab };
    });
  }, []);

  const setActiveTab = useCallback((tabId: string | null) => {
    setState(prev => ({ ...prev, activeTab: tabId }));
  }, []);

  const updateTabContent = useCallback((tabId: string, content: string) => {
    setState(prev => ({
      ...prev,
      openTabs: prev.openTabs.map(tab =>
        tab.id === tabId ? { ...tab, content, isDirty: true } : tab
      )
    }));
  }, []);

  const toggleSidebar = useCallback(() => {
    setState(prev => ({ ...prev, sidebarVisible: !prev.sidebarVisible }));
  }, []);

  const toggleTerminal = useCallback(() => {
    setState(prev => ({ ...prev, terminalVisible: !prev.terminalVisible }));
  }, []);

  const togglePreview = useCallback(() => {
    setState(prev => ({ ...prev, previewVisible: !prev.previewVisible }));
  }, []);

  const setSidebarView = useCallback((view: IDEState['activeSidebarView']) => {
    setState(prev => ({ ...prev, activeSidebarView: view }));
  }, []);

  return (
    <IDEContext.Provider
      value={{
        state,
        setWorkspace,
        openFile,
        closeTab,
        setActiveTab,
        updateTabContent,
        toggleSidebar,
        toggleTerminal,
        togglePreview,
        setSidebarView,
        isElectron
      }}
    >
      {children}
    </IDEContext.Provider>
  );
};

export const useIDE = () => {
  const context = useContext(IDEContext);
  if (!context) {
    throw new Error('useIDE must be used within IDEProvider');
  }
  return context;
};



