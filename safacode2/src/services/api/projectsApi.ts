/**
 * SafeCode IDE - Projects API Service
 */

const API_BASE = import.meta.env.VITE_API_BASE || '/safecode/api';

export interface Project {
  id: number;
  user_id: number;
  name: string;
  slug: string;
  description?: string;
  is_public: boolean;
  is_template: boolean;
  icon_url?: string;
  color?: string;
  default_language?: string;
  created_at: string;
  updated_at: string;
  last_accessed_at?: string;
  file_count?: number;
  collaborator_count?: number;
}

export interface CreateProjectData {
  name: string;
  description?: string;
  color?: string;
  default_language?: string;
}

export interface UpdateProjectData {
  id: number;
  name?: string;
  description?: string;
  color?: string;
  default_language?: string;
  is_public?: boolean;
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
 * Listar projetos do usuário
 */
export async function listProjects(): Promise<Project[]> {
  const data = await apiRequest<{ projects: Project[] }>('/projects.php?action=list');
  return data.projects;
}

/**
 * Buscar projeto específico
 */
export async function getProject(id: number): Promise<Project> {
  const data = await apiRequest<{ project: Project }>(`/projects.php?action=get&id=${id}`);
  return data.project;
}

/**
 * Criar novo projeto
 */
export async function createProject(projectData: CreateProjectData): Promise<Project> {
  const data = await apiRequest<{ project: Project }>('/projects.php?action=create', {
    method: 'POST',
    body: JSON.stringify(projectData),
  });
  return data.project;
}

/**
 * Atualizar projeto
 */
export async function updateProject(projectData: UpdateProjectData): Promise<Project> {
  const data = await apiRequest<{ project: Project }>('/projects.php?action=update', {
    method: 'PUT',
    body: JSON.stringify(projectData),
  });
  return data.project;
}

/**
 * Deletar projeto
 */
export async function deleteProject(id: number): Promise<void> {
  await apiRequest(`/projects.php?action=delete&id=${id}`);
}

