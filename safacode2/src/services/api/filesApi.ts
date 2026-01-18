/**
 * SafeCode IDE - Files API Service
 */

import type { FileNode } from '@/types/ide';

const API_BASE = import.meta.env.VITE_API_BASE || '/safecode/api';

export interface FileRecord {
  id: number;
  project_id: number;
  parent_id?: number;
  name: string;
  path: string;
  type: 'file' | 'folder';
  content?: string;
  language?: string;
  size: number;
  encoding: string;
  is_binary: boolean;
  mime_type?: string;
  created_at: string;
  updated_at: string;
  created_by?: number;
  updated_by?: number;
}

export interface CreateFileData {
  project_id: number;
  parent_id?: number;
  name: string;
  type: 'file' | 'folder';
  content?: string;
  language?: string;
}

/**
 * Obter token de autenticação
 */
function getAuthToken(): string | null {
  const authStorage = localStorage.getItem('safecode-auth-storage');
  if (!authStorage) return null;
  
  try {
    const parsed = JSON.parse(authStorage);
    return parsed.state?.token || null;
  } catch {
    return null;
  }
}

/**
 * Fazer requisição autenticada
 */
async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const token = getAuthToken();
  
  const response = await fetch(`${API_BASE}${endpoint}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token && { Authorization: `Bearer ${token}` }),
      ...options.headers,
    },
  });

  const data = await response.json();

  if (!response.ok || !data.success) {
    throw new Error(data.error || 'Erro na requisição');
  }

  return data;
}

/**
 * Converter FileRecord para FileNode
 */
function recordToFileNode(record: FileRecord): FileNode {
  return {
    id: String(record.id),
    name: record.name,
    type: record.type,
    path: record.path,
    language: record.language || undefined,
    content: record.content || undefined,
    children: record.type === 'folder' ? [] : undefined,
  };
}

/**
 * Converter árvore de FileRecord para FileNode[]
 */
function treeToFileNodes(tree: FileRecord[]): FileNode[] {
  return tree.map(record => {
    const node = recordToFileNode(record);
    if (record.type === 'folder' && 'children' in record && Array.isArray(record.children)) {
      node.children = treeToFileNodes(record.children as FileRecord[]);
    }
    return node;
  });
}

/**
 * Listar arquivos do projeto (flat)
 */
export async function listFiles(projectId: number): Promise<FileRecord[]> {
  const data = await apiRequest<{ files: FileRecord[] }>(`/files.php?action=list&project_id=${projectId}`);
  return data.files;
}

/**
 * Buscar árvore de arquivos do projeto
 */
export async function getFileTree(projectId: number): Promise<FileNode[]> {
  const data = await apiRequest<{ tree: FileRecord[] }>(`/files.php?action=tree&project_id=${projectId}`);
  return treeToFileNodes(data.tree);
}

/**
 * Buscar arquivo específico
 */
export async function getFile(id: number): Promise<FileNode> {
  const data = await apiRequest<{ file: FileRecord }>(`/files.php?action=get&id=${id}`);
  return recordToFileNode(data.file);
}

/**
 * Criar arquivo ou pasta
 */
export async function createFile(fileData: CreateFileData): Promise<FileNode> {
  const data = await apiRequest<{ file: FileRecord }>('/files.php?action=create', {
    method: 'POST',
    body: JSON.stringify(fileData),
  });
  return recordToFileNode(data.file);
}

/**
 * Atualizar arquivo
 */
export async function updateFile(id: number, content: string): Promise<FileNode> {
  const data = await apiRequest<{ file: FileRecord }>('/files.php?action=update', {
    method: 'PUT',
    body: JSON.stringify({ id, content }),
  });
  return recordToFileNode(data.file);
}

/**
 * Deletar arquivo ou pasta
 */
export async function deleteFile(id: number): Promise<void> {
  await apiRequest(`/files.php?action=delete&id=${id}`);
}

