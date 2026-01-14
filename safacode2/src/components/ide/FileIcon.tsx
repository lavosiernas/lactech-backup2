import { 
  FileCode2, 
  FileJson, 
  FileText, 
  File, 
  FileType,
  Folder, 
  FolderOpen,
  FileImage,
  FileVideo,
  Settings,
  Package
} from 'lucide-react';
import type { FileNode } from '@/types/ide';

interface FileIconProps {
  file: FileNode;
  className?: string;
}

const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  // JavaScript/TypeScript
  'js': FileCode2,
  'jsx': FileCode2,
  'ts': FileCode2,
  'tsx': FileCode2,
  // Web
  'html': FileType,
  'css': FileType,
  'scss': FileType,
  'less': FileType,
  // Data
  'json': FileJson,
  'xml': FileJson,
  'yaml': FileJson,
  'yml': FileJson,
  // Docs
  'md': FileText,
  'txt': FileText,
  'readme': FileText,
  // Images
  'png': FileImage,
  'jpg': FileImage,
  'jpeg': FileImage,
  'gif': FileImage,
  'svg': FileImage,
  'webp': FileImage,
  // Video
  'mp4': FileVideo,
  'webm': FileVideo,
  // Config
  'config': Settings,
  'env': Settings,
  // Package
  'package': Package,
};

const colorMap: Record<string, string> = {
  'js': 'text-yellow-400',
  'jsx': 'text-yellow-400',
  'ts': 'text-blue-400',
  'tsx': 'text-blue-400',
  'html': 'text-orange-400',
  'css': 'text-blue-300',
  'scss': 'text-pink-400',
  'json': 'text-yellow-300',
  'md': 'text-gray-300',
  'svg': 'text-green-400',
  'png': 'text-purple-400',
  'jpg': 'text-purple-400',
};

export const FileIcon: React.FC<FileIconProps> = ({ file, className = '' }) => {
  if (file.type === 'folder') {
    const FolderIcon = file.isExpanded ? FolderOpen : Folder;
    return <FolderIcon className={`w-4 h-4 text-blue-400 ${className}`} />;
  }

  const extension = file.name.split('.').pop()?.toLowerCase() || '';
  const Icon = iconMap[extension] || File;
  const color = colorMap[extension] || 'text-muted-foreground';

  return <Icon className={`w-4 h-4 ${color} ${className}`} />;
};

export const getLanguageFromFile = (filename: string): string => {
  const extension = filename.split('.').pop()?.toLowerCase() || '';
  const languageMap: Record<string, string> = {
    'js': 'javascript',
    'jsx': 'javascript',
    'ts': 'typescript',
    'tsx': 'typescript',
    'html': 'html',
    'css': 'css',
    'scss': 'scss',
    'less': 'less',
    'json': 'json',
    'md': 'markdown',
    'py': 'python',
    'php': 'php',
    'rb': 'ruby',
    'go': 'go',
    'rs': 'rust',
    'java': 'java',
    'c': 'c',
    'cpp': 'cpp',
    'h': 'c',
    'hpp': 'cpp',
    'sh': 'shell',
    'bash': 'shell',
    'sql': 'sql',
    'yaml': 'yaml',
    'yml': 'yaml',
    'xml': 'xml',
  };
  return languageMap[extension] || 'plaintext';
};
