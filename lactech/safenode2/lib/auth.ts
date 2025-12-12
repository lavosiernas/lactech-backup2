import Cookies from 'js-cookie'

const TOKEN_KEY = 'safenode_token'
const USER_KEY = 'safenode_user'

export interface AuthUser {
  id: number
  email: string
  name: string
}

export const auth = {
  // Salvar token e dados do usuário
  setAuth: (token: string, user: AuthUser) => {
    Cookies.set(TOKEN_KEY, token, { expires: 7, secure: true, sameSite: 'strict' })
    Cookies.set(USER_KEY, JSON.stringify(user), { expires: 7, secure: true, sameSite: 'strict' })
  },

  // Obter token
  getToken: (): string | undefined => {
    return Cookies.get(TOKEN_KEY)
  },

  // Obter usuário
  getUser: (): AuthUser | null => {
    const userStr = Cookies.get(USER_KEY)
    if (!userStr) return null
    try {
      return JSON.parse(userStr)
    } catch {
      return null
    }
  },

  // Verificar se está autenticado
  isAuthenticated: (): boolean => {
    return !!Cookies.get(TOKEN_KEY)
  },

  // Limpar autenticação
  clearAuth: () => {
    Cookies.remove(TOKEN_KEY)
    Cookies.remove(USER_KEY)
  },

  // Verificar se precisa redirecionar para login
  requireAuth: (): boolean => {
    if (!auth.isAuthenticated()) {
      if (typeof window !== 'undefined') {
        window.location.href = '/login'
      }
      return false
    }
    return true
  },
}





