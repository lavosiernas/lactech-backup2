export interface FileNode {
  id: string;
  name: string;
  type: 'file' | 'folder';
  path: string;
  children?: FileNode[];
  content?: string;
  language?: string;
  isExpanded?: boolean;
}

export interface EditorTab {
  id: string;
  name: string;
  path: string;
  content: string;
  language: string;
  isDirty: boolean;
  cursorPosition: { line: number; column: number };
}

export interface TerminalInstance {
  id: string;
  name: string;
  history: TerminalLine[];
  currentInput: string;
}

export interface TerminalLine {
  type: 'input' | 'output' | 'error' | 'info';
  content: string;
  timestamp: Date;
}

export interface GitStatus {
  branch: string;
  staged: string[];
  modified: string[];
  untracked: string[];
}

export interface SyntaxColors {
  comment: string;
  keyword: string;
  string: string;
  number: string;
  type: string;
  function: string;
  variable: string;
  operator: string;
}

export interface IDESettings {
  fontSize: number;
  tabSize: number;
  theme: 'dark' | 'light';
  autoSave: boolean;
  wordWrap: boolean;
  minimap: boolean;
  syntaxColors?: SyntaxColors;
}

export interface Command {
  id: string;
  label: string;
  shortcut?: string;
  action: () => void;
  category?: string;
}

export type PanelType = 'explorer' | 'search' | 'git' | 'extensions';
export type PreviewMode = 'desktop' | 'tablet' | 'ios' | 'android';
