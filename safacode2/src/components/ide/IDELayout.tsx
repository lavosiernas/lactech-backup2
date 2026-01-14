import { useEffect } from 'react';
import { Sidebar } from './Sidebar';
import { MenuBar } from './MenuBar';
import { EditorTabs } from './EditorTabs';
import { CodeEditor } from './CodeEditor';
import { Terminal } from './Terminal';
import { LivePreview } from './LivePreview';
import { StatusBar } from './StatusBar';
import { CommandPalette } from './CommandPalette';
import { WelcomeScreen } from './WelcomeScreen';
import { FindReplace } from './FindReplace';
import { AchievementModal } from './AchievementModal';
import { useIDEStore } from '@/stores/ideStore';

export const IDELayout: React.FC = () => {
  const { 
    sidebarOpen, 
    terminalOpen, 
    previewOpen, 
    showWelcome,
    toggleSidebar,
    toggleTerminal,
    togglePreview,
    toggleCommandPalette,
    toggleFindReplace,
    activeTabId,
    tabs,
    saveTab,
    closeTab,
    createFile,
    setSidebarPanel,
    addTerminal,
    terminals,
    addTerminalLine
  } = useIDEStore();

  // Global keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // Command Palette: Ctrl+Shift+P
      if (e.ctrlKey && e.shiftKey && e.key === 'P') {
        e.preventDefault();
        toggleCommandPalette();
      }
      // Toggle Sidebar: Ctrl+B
      else if (e.ctrlKey && e.key === 'b') {
        e.preventDefault();
        toggleSidebar();
      }
      // Toggle Terminal: Ctrl+`
      else if (e.ctrlKey && e.key === '`') {
        e.preventDefault();
        toggleTerminal();
      }
      // Toggle Preview: Ctrl+Shift+V
      else if (e.ctrlKey && e.shiftKey && e.key === 'V') {
        e.preventDefault();
        togglePreview();
      }
      // Find: Ctrl+F
      else if (e.ctrlKey && e.key === 'f' && !e.shiftKey) {
        e.preventDefault();
        toggleFindReplace();
      }
      // New File: Ctrl+N - handled by MenuBar
      // else if (e.ctrlKey && e.key === 'n') {
      //   e.preventDefault();
      //   // Handled by MenuBar component
      // }
      // Save: Ctrl+S
      else if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        if (activeTabId) saveTab(activeTabId);
      }
      // Close Tab: Ctrl+W
      else if (e.ctrlKey && e.key === 'w') {
        e.preventDefault();
        if (activeTabId) closeTab(activeTabId);
      }
      // Explorer: Ctrl+Shift+E
      else if (e.ctrlKey && e.shiftKey && e.key === 'E') {
        e.preventDefault();
        setSidebarPanel('explorer');
      }
      // Search: Ctrl+Shift+F
      else if (e.ctrlKey && e.shiftKey && e.key === 'F') {
        e.preventDefault();
        setSidebarPanel('search');
      }
      // Source Control: Ctrl+Shift+G
      else if (e.ctrlKey && e.shiftKey && e.key === 'G') {
        e.preventDefault();
        setSidebarPanel('git');
      }
      // New Terminal: Ctrl+Shift+`
      else if (e.ctrlKey && e.shiftKey && e.key === '`') {
        e.preventDefault();
        addTerminal();
        toggleTerminal();
      }
      // Run: F5
      else if (e.key === 'F5' && !e.ctrlKey && !e.shiftKey) {
        e.preventDefault();
        const activeTab = tabs.find(t => t.id === activeTabId);
        if (activeTab) {
          const terminal = terminals[0];
          if (terminal) {
            addTerminalLine(terminal.id, {
              type: 'info',
              content: `Running ${activeTab.name}...`
            });
          }
          toggleTerminal();
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [toggleSidebar, toggleTerminal, togglePreview, toggleCommandPalette, toggleFindReplace, activeTabId, tabs, saveTab, closeTab, createFile, setSidebarPanel, addTerminal, terminals, addTerminalLine]);

  return (
    <div className="h-screen flex flex-col bg-background overflow-hidden">
      {/* Menu bar */}
      <MenuBar />

      {/* Main content */}
      <div className="flex-1 flex overflow-hidden">
        {/* Sidebar */}
        <Sidebar />

        {/* Editor + Terminal area */}
        <div className="flex-1 flex flex-col overflow-hidden">
          <div className="flex-1 flex overflow-hidden">
            {/* Editor area */}
            <div className="flex-1 flex flex-col overflow-hidden relative">
              <EditorTabs />
              {showWelcome && !activeTabId ? (
                <WelcomeScreen />
              ) : (
                <CodeEditor />
              )}
              <FindReplace />
            </div>

            {/* Preview panel */}
            {previewOpen && (
              <>
                <div className="w-px bg-panel-border hover:bg-primary/40 cursor-col-resize transition-colors" />
                <div className="w-[480px] flex-shrink-0">
                  <LivePreview />
                </div>
              </>
            )}
          </div>

          {/* Terminal */}
          {terminalOpen && (
            <>
              <div className="h-px bg-panel-border hover:bg-primary/40 cursor-row-resize transition-colors" />
              <div className="h-64 flex-shrink-0">
                <Terminal />
              </div>
            </>
          )}
        </div>
      </div>

      {/* Status bar */}
      <StatusBar />

      {/* Command Palette */}
      <CommandPalette />

      {/* Achievement Modal */}
      <AchievementModal />
    </div>
  );
};
