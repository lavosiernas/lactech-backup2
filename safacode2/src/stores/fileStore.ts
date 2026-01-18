/**
 * SafeCode IDE - File Store
 * Gerencia arquivos do projeto integrado com o banco de dados
 */

import { create } from 'zustand';
import * as filesApi from '@/services/api/filesApi';
import type { FileNode } from '@/types/ide';

interface FileState {
  files: FileNode[];
  isLoading: boolean;
  error: string | null;
  currentProjectId: number | null;
  
  // Actions
  loadFiles: (projectId: number) => Promise<void>;
  createFile: (projectId: number, parentPath: string, name: string, content?: string) => Promise<void>;
  createFolder: (projectId: number, parentPath: string, name: string) => Promise<void>;
  updateFile: (fileId: string, content: string) => Promise<void>;
  deleteFile: (fileId: string) => Promise<void>;
  setCurrentProject: (projectId: number | null) => void;
}

export const useFileStore = create<FileState>((set, get) => ({
  files: [],
  isLoading: false,
  error: null,
  currentProjectId: null,

  loadFiles: async (projectId: number) => {
    set({ isLoading: true, error: null, currentProjectId: projectId });
    try {
      const tree = await filesApi.getFileTree(projectId);
      set({ files: tree, isLoading: false });
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Erro ao carregar arquivos',
        isLoading: false 
      });
    }
  },

  createFile: async (projectId: number, parentPath: string, name: string, content = '') => {
    try {
      // Encontrar parent_id se necessário
      let parentId: number | undefined;
      if (parentPath !== '/') {
        const { files } = get();
        const parent = findFileByPath(files, parentPath);
        if (parent) {
          parentId = parseInt(parent.id);
        }
      }

      const newFile = await filesApi.createFile({
        project_id: projectId,
        parent_id: parentId,
        name,
        type: 'file',
        content,
      });

      // Atualizar árvore local
      const { files } = get();
      const updatedFiles = addFileToTree(files, newFile, parentPath);
      set({ files: updatedFiles });
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Erro ao criar arquivo'
      });
      throw error;
    }
  },

  createFolder: async (projectId: number, parentPath: string, name: string) => {
    try {
      let parentId: number | undefined;
      if (parentPath !== '/') {
        const { files } = get();
        const parent = findFileByPath(files, parentPath);
        if (parent) {
          parentId = parseInt(parent.id);
        }
      }

      const newFolder = await filesApi.createFile({
        project_id: projectId,
        parent_id: parentId,
        name,
        type: 'folder',
      });

      const { files } = get();
      const updatedFiles = addFileToTree(files, newFolder, parentPath);
      set({ files: updatedFiles });
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Erro ao criar pasta'
      });
      throw error;
    }
  },

  updateFile: async (fileId: string, content: string) => {
    try {
      const id = parseInt(fileId);
      await filesApi.updateFile(id, content);
      
      // Atualizar localmente
      const { files } = get();
      const updatedFiles = updateFileInTree(files, fileId, content);
      set({ files: updatedFiles });
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Erro ao atualizar arquivo'
      });
      throw error;
    }
  },

  deleteFile: async (fileId: string) => {
    try {
      const id = parseInt(fileId);
      await filesApi.deleteFile(id);
      
      // Remover da árvore local
      const { files } = get();
      const updatedFiles = removeFileFromTree(files, fileId);
      set({ files: updatedFiles });
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Erro ao deletar arquivo'
      });
      throw error;
    }
  },

  setCurrentProject: (projectId) => {
    set({ currentProjectId: projectId, files: [] });
  },
}));

// Helper functions
function findFileByPath(nodes: FileNode[], path: string): FileNode | null {
  for (const node of nodes) {
    if (node.path === path) return node;
    if (node.children) {
      const found = findFileByPath(node.children, path);
      if (found) return found;
    }
  }
  return null;
}

function addFileToTree(nodes: FileNode[], newFile: FileNode, parentPath: string): FileNode[] {
  if (parentPath === '/') {
    return [...nodes, newFile];
  }
  
  return nodes.map(node => {
    if (node.path === parentPath && node.type === 'folder') {
      return {
        ...node,
        children: [...(node.children || []), newFile],
      };
    }
    if (node.children) {
      return {
        ...node,
        children: addFileToTree(node.children, newFile, parentPath),
      };
    }
    return node;
  });
}

function updateFileInTree(nodes: FileNode[], fileId: string, content: string): FileNode[] {
  return nodes.map(node => {
    if (node.id === fileId) {
      return { ...node, content };
    }
    if (node.children) {
      return {
        ...node,
        children: updateFileInTree(node.children, fileId, content),
      };
    }
    return node;
  });
}

function removeFileFromTree(nodes: FileNode[], fileId: string): FileNode[] {
  return nodes
    .filter(node => node.id !== fileId)
    .map(node => {
      if (node.children) {
        return {
          ...node,
          children: removeFileFromTree(node.children, fileId),
        };
      }
      return node;
    });
}

