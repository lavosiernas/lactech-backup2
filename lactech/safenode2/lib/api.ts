import axios, { AxiosInstance, AxiosError } from 'axios'
import Cookies from 'js-cookie'
import type { ApiResponse } from '@/types'

// Configuração base da API
// Usar o proxy Next.js que redireciona para /api/php-proxy
const API_BASE_URL = '/api/php'

// Criar instância do axios
const api: AxiosInstance = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
})

// Interceptor para adicionar token de autenticação
api.interceptors.request.use(
  (config) => {
    const token = Cookies.get('safenode_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Interceptor para tratar erros
api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      // Token inválido ou expirado
      Cookies.remove('safenode_token')
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)

// Função auxiliar para fazer requisições
async function request<T = any>(
  method: 'GET' | 'POST' | 'PUT' | 'DELETE',
  endpoint: string,
  data?: any
): Promise<ApiResponse<T>> {
  try {
    const response = await api.request<ApiResponse<T>>({
      method,
      url: endpoint,
      data,
    })
    return response.data
  } catch (error) {
    if (axios.isAxiosError(error)) {
      return {
        success: false,
        error: error.response?.data?.error || error.message || 'Erro desconhecido',
      }
    }
    return {
      success: false,
      error: 'Erro desconhecido',
    }
  }
}

// API de estatísticas
export const statsApi = {
  getIndexStats: () => request('GET', '/dashboard-stats.php'),
  getIntegrationStats: () => request('GET', '/dashboard-stats.php?type=integration'),
  getRecentLogs: (limit = 5) => request('GET', `/dashboard-stats.php?type=logs&limit=${limit}`),
}

// API de autenticação
export const authApi = {
  login: async (email: string, password: string, hvToken: string) => {
    try {
      // Usar endpoint Next.js que faz proxy para login.php
      const response = await fetch('/api/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Incluir cookies
        body: JSON.stringify({
          email,
          password,
          hv_token: hvToken,
          safenode_hv_js: '1',
        }),
      })
      
      const data = await response.json()
      return data
    } catch (error) {
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Erro ao fazer login',
      }
    }
  },
  register: (data: any) => request('POST', '/register.php', data),
  logout: () => request('POST', '/logout.php'),
  verifyOTP: (otp: string) => request('POST', '/verify-otp.php', { otp }),
  forgotPassword: (email: string) => request('POST', '/forgot-password.php', { email }),
}

// API de sites
export const sitesApi = {
  getAll: () => request('GET', '/sites.php'),
  getById: (id: number) => request('GET', `/sites.php?id=${id}`),
  create: (data: any) => request('POST', '/sites.php', data),
  update: (id: number, data: any) => request('PUT', `/sites.php?id=${id}`, data),
  delete: (id: number) => request('DELETE', `/sites.php?id=${id}`),
}

// API de logs
export const logsApi = {
  getAll: (params?: { limit?: number; offset?: number; site_id?: number }) => {
    const query = new URLSearchParams()
    if (params?.limit) query.append('limit', params.limit.toString())
    if (params?.offset) query.append('offset', params.offset.toString())
    if (params?.site_id) query.append('site_id', params.site_id.toString())
    return request('GET', `/logs.php?${query.toString()}`)
  },
  getById: (id: number) => request('GET', `/logs.php?id=${id}`),
}

// API de segurança
export const securityApi = {
  getAnalytics: () => request('GET', '/security-analytics.php'),
  getSuspiciousIPs: () => request('GET', '/suspicious-ips.php'),
  getAttackedTargets: () => request('GET', '/attacked-targets.php'),
  getBehaviorAnalysis: () => request('GET', '/behavior-analysis.php'),
}

export default api

