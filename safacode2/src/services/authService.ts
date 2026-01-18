/**
 * SafeCode IDE - Authentication Service
 * Funções auxiliares para autenticação
 */

import { useAuthStore } from '@/stores/authStore';

const API_BASE = import.meta.env.VITE_API_BASE || '/safecode/api';

export const authService = {
  /**
   * Fazer requisição autenticada
   */
  async fetchWithAuth(url: string, options: RequestInit = {}) {
    const token = useAuthStore.getState().token;
    
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers,
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    return fetch(`${API_BASE}${url}`, {
      ...options,
      headers,
    });
  },

  /**
   * Verificar se está autenticado
   */
  isAuthenticated(): boolean {
    return useAuthStore.getState().isAuthenticated;
  },

  /**
   * Obter usuário atual
   */
  getCurrentUser() {
    return useAuthStore.getState().user;
  },
};

