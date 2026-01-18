/**
 * SafeCode IDE - Project Store
 * Gerencia projetos do usuário integrado com o banco de dados
 */

import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import * as projectsApi from '@/services/api/projectsApi';
import type { Project } from '@/services/api/projectsApi';

interface ProjectState {
  projects: Project[];
  currentProject: Project | null;
  isLoading: boolean;
  error: string | null;
  
  // Actions
  loadProjects: () => Promise<void>;
  loadProject: (id: number) => Promise<void>;
  createProject: (data: { name: string; description?: string; color?: string }) => Promise<Project>;
  updateProject: (id: number, data: Partial<Project>) => Promise<void>;
  deleteProject: (id: number) => Promise<void>;
  setCurrentProject: (project: Project | null) => void;
}

export const useProjectStore = create<ProjectState>()(
  persist(
    (set, get) => ({
      projects: [],
      currentProject: null,
      isLoading: false,
      error: null,

      loadProjects: async () => {
        set({ isLoading: true, error: null });
        try {
          const projects = await projectsApi.listProjects();
          set({ projects, isLoading: false });
        } catch (error) {
          set({ 
            error: error instanceof Error ? error.message : 'Erro ao carregar projetos',
            isLoading: false 
          });
        }
      },

      loadProject: async (id: number) => {
        set({ isLoading: true, error: null });
        try {
          const project = await projectsApi.getProject(id);
          set({ currentProject: project, isLoading: false });
          
          // Atualizar na lista também
          const { projects } = get();
          const updatedProjects = projects.map(p => p.id === id ? project : p);
          set({ projects: updatedProjects });
        } catch (error) {
          set({ 
            error: error instanceof Error ? error.message : 'Erro ao carregar projeto',
            isLoading: false 
          });
        }
      },

      createProject: async (data) => {
        set({ isLoading: true, error: null });
        try {
          const project = await projectsApi.createProject(data);
          set(state => ({
            projects: [project, ...state.projects],
            currentProject: project,
            isLoading: false
          }));
          return project;
        } catch (error) {
          set({ 
            error: error instanceof Error ? error.message : 'Erro ao criar projeto',
            isLoading: false 
          });
          throw error;
        }
      },

      updateProject: async (id: number, data: Partial<Project>) => {
        set({ isLoading: true, error: null });
        try {
          const updated = await projectsApi.updateProject({ id, ...data });
          set(state => ({
            projects: state.projects.map(p => p.id === id ? updated : p),
            currentProject: state.currentProject?.id === id ? updated : state.currentProject,
            isLoading: false
          }));
        } catch (error) {
          set({ 
            error: error instanceof Error ? error.message : 'Erro ao atualizar projeto',
            isLoading: false 
          });
          throw error;
        }
      },

      deleteProject: async (id: number) => {
        set({ isLoading: true, error: null });
        try {
          await projectsApi.deleteProject(id);
          set(state => ({
            projects: state.projects.filter(p => p.id !== id),
            currentProject: state.currentProject?.id === id ? null : state.currentProject,
            isLoading: false
          }));
        } catch (error) {
          set({ 
            error: error instanceof Error ? error.message : 'Erro ao deletar projeto',
            isLoading: false 
          });
          throw error;
        }
      },

      setCurrentProject: (project) => {
        set({ currentProject: project });
      },
    }),
    {
      name: 'safecode-project-storage',
      partialize: (state) => ({ 
        currentProject: state.currentProject 
      }),
    }
  )
);

