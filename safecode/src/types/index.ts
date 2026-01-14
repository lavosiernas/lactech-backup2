export interface FileItem {
  name: string;
  path: string;
  isDirectory: boolean;
  children?: FileItem[];
}

export interface EditorTab {
  id: string;
  filePath: string;
  fileName: string;
  content: string;
  isDirty: boolean;
  language?: string;
}

export interface TerminalInstance {
  id: string;
  title: string;
}

export interface IDEState {
  workspace: string | null;
  openTabs: EditorTab[];
  activeTab: string | null;
  sidebarVisible: boolean;
  terminalVisible: boolean;
  previewVisible: boolean;
  activeSidebarView: 'explorer' | 'search' | 'git' | 'extensions';
}



