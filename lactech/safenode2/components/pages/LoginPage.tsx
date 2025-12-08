'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import Link from 'next/link'
import Image from 'next/image'
import { authApi } from '@/lib/api'
import { auth } from '@/lib/auth'
import { AlertCircle, CheckCircle2, Eye, EyeOff } from 'lucide-react'

export default function LoginPage() {
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [showPassword, setShowPassword] = useState(false)
  const [remember, setRemember] = useState(false)
  const [hvToken, setHvToken] = useState('')
  const [hvVerified, setHvVerified] = useState(false)
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    // Se já estiver autenticado, redirecionar
    if (auth.isAuthenticated()) {
      router.push('/dashboard')
    }

    // Inicializar verificação humana (HV)
    initHumanVerification()
  }, [router])

  const initHumanVerification = async () => {
    try {
      // Simular verificação humana SafeNode
      setTimeout(() => {
        setHvVerified(true)
        setHvToken('verified_token_' + Date.now())
      }, 800)
    } catch (error) {
      console.error('Erro ao inicializar verificação humana:', error)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setLoading(true)

    try {
      const response = await authApi.login(email, password, hvToken)
      
      if (response.success && response.data) {
        // Salvar token e dados do usuário
        const { token, user } = response.data as any
        auth.setAuth(token, user)
        
        // Redirecionar para dashboard
        router.push('/dashboard')
      } else {
        setError(response.error || 'Erro ao fazer login')
        // Reinicializar HV em caso de erro
        setHvVerified(false)
        initHumanVerification()
      }
    } catch (error: any) {
      console.error('Erro no login:', error)
      setError(error?.response?.data?.error || error?.message || 'Erro ao conectar com o servidor')
      setHvVerified(false)
      initHumanVerification()
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="login-page min-h-screen h-full flex flex-col md:flex-row overflow-hidden bg-white">
      {/* Left Side: Image & Branding (Desktop Only) */}
      <div className="hidden md:flex md:w-1/2 lg:w-[55%] relative bg-black text-white overflow-hidden">
        {/* Background Image */}
        <div
          className="absolute inset-0 w-full h-full bg-cover bg-center opacity-50"
          style={{
            backgroundImage: "url('https://i.postimg.cc/6pqLFX9H/emailotp-(10).jpg')",
            backgroundPosition: 'center',
            backgroundRepeat: 'no-repeat',
            backgroundSize: 'cover',
          }}
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-black/20"></div>

        {/* Content Overlay */}
        <div className="relative z-10 flex flex-col justify-between w-full p-12 lg:p-16">
          {/* Logo */}
          <div className="flex items-center gap-3">
            <div className="bg-white/10 p-2 rounded-lg backdrop-blur-md flex items-center justify-center">
              <img
                src="/assets/img/logos (6).png"
                alt="SafeNode"
                className="w-6 h-6 object-contain"
                onError={(e) => {
                  // Fallback: usar um ícone SVG se a imagem não carregar
                  e.currentTarget.outerHTML = '<svg class="w-6 h-6" fill="white" viewBox="0 0 24 24"><path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/></svg>'
                }}
              />
            </div>
            <span className="text-xl font-bold tracking-tight">SafeNode</span>
          </div>

          {/* Quote */}
          <div className="max-w-md">
            <blockquote className="text-2xl font-medium leading-snug mb-6">
              "A segurança não é apenas uma barreira, é a fundação que permite que sua equipe inove sem medo."
            </blockquote>
            <div className="flex items-center gap-4">
              <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center text-sm font-bold">
                JS
              </div>
              <div>
                <div className="font-semibold">João Silva</div>
                <div className="text-sm text-slate-400">Diretor de Segurança da Informação</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Right Side: Login Form */}
      <div className="w-full md:w-1/2 lg:w-[45%] flex flex-col justify-center overflow-y-auto">
        <div className="w-full max-w-md mx-auto px-6 py-12 md:px-10 lg:px-12">
          {/* Header (Mobile Logo) */}
          <div className="md:hidden mb-8 flex items-center gap-2">
            <img 
              src="/assets/img/logos (5).png" 
              alt="SafeNode" 
              className="w-8 h-8"
              onError={(e) => {
                e.currentTarget.style.display = 'none'
              }}
            />
            <span className="text-xl font-bold text-slate-900">SafeNode</span>
          </div>

          <div className="mb-10">
            <h1 className="text-3xl font-bold text-slate-900 mb-2">Bem-vindo de volta</h1>
            <p className="text-slate-500">Acesse seu painel e gerencie seus sistemas.</p>
          </div>

          {error && (
            <div className="mb-6 p-5 rounded-xl bg-red-500 border-2 border-red-600 shadow-lg shadow-red-500/20 flex items-start gap-4 animate-fade-in">
              <div className="flex-shrink-0 w-7 h-7 rounded-full bg-red-600 flex items-center justify-center">
                <AlertCircle className="w-5 h-5 text-white" />
              </div>
              <p className="text-base text-white font-bold leading-relaxed">{error}</p>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-6" id="loginForm">
            {/* Email */}
            <div className="space-y-1.5">
              <label htmlFor="email" className="block text-sm font-medium text-slate-700">
                Email
              </label>
              <input
                type="email"
                name="email"
                id="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="block w-full px-4 py-3 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                placeholder="exemplo@email.com"
              />
            </div>

            {/* Password */}
            <div className="space-y-1.5">
              <label htmlFor="password" className="block text-sm font-medium text-slate-700">
                Senha
              </label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  name="password"
                  id="password"
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="block w-full px-4 py-3 pr-12 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-black/10 focus:border-black transition-all text-sm"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                >
                  {showPassword ? (
                    <EyeOff className="w-5 h-5" />
                  ) : (
                    <Eye className="w-5 h-5" />
                  )}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between text-sm">
              {/* Remember Me Toggle */}
              <div className="flex items-center">
                <div className="relative inline-block w-10 mr-2 align-middle select-none transition duration-200 ease-in">
                  <input
                    type="checkbox"
                    name="remember"
                    id="remember"
                    checked={remember}
                    onChange={(e) => setRemember(e.target.checked)}
                    className="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-4 appearance-none cursor-pointer transition-all duration-300"
                  />
                  <label
                    htmlFor="remember"
                    className="toggle-label block overflow-hidden h-5 rounded-full bg-slate-300 cursor-pointer"
                  />
                </div>
                <label htmlFor="remember" className="text-sm text-slate-600 cursor-pointer select-none">
                  Lembrar-me
                </label>
              </div>

              <Link href="/forgot-password" className="text-sm font-semibold text-black hover:underline">
                Esqueceu a senha?
              </Link>
            </div>

            {/* Verificação Humana SafeNode */}
            <div className="mt-3 p-3 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-3 shadow-sm" id="hv-box">
              <div className="relative flex items-center justify-center w-9 h-9">
                {!hvVerified && (
                  <div className="absolute inset-0 rounded-2xl border-2 border-slate-200 border-t-black animate-spin" id="hv-spinner"></div>
                )}
                <div className="relative z-10 w-7 h-7 rounded-2xl bg-black flex items-center justify-center">
                  <img
                    src="/assets/img/logos (6).png"
                    alt="SafeNode"
                    className="w-4 h-4 object-contain"
                    onError={(e) => {
                      e.currentTarget.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"%3E%3Cpath d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/%3E%3C/svg%3E'
                    }}
                  />
                </div>
              </div>
              <div className="flex-1">
                <p className="text-xs font-semibold text-slate-900 flex items-center gap-1">
                  SafeNode <span className="text-[10px] font-normal text-slate-500">verificação humana</span>
                </p>
                <p className="text-[11px] text-slate-500" id="hv-text">
                  {hvVerified ? 'Verificado com SafeNode' : 'Validando interação do navegador…'}
                </p>
              </div>
              {hvVerified && (
                <CheckCircle2 className="w-4 h-4 text-emerald-500" id="hv-check" />
              )}
            </div>
            <input type="hidden" name="safenode_hv_token" value={hvToken} />
            <input type="hidden" name="safenode_hv_js" id="safenode_hv_js" value="1" />

            {/* Submit Button */}
            <button
              type="submit"
              disabled={loading || !hvVerified}
              className="w-full flex justify-center items-center py-3 px-4 rounded-lg shadow-sm text-sm font-semibold text-white bg-black hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-900 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              id="loginBtn"
            >
              {loading && (
                <span className="loading-spinner mr-2"></span>
              )}
              <span id="loginText">{loading ? 'Autenticando...' : 'Entrar'}</span>
            </button>

            {/* Divider */}
            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-slate-200"></div>
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-slate-500 uppercase text-xs font-medium">Ou</span>
              </div>
            </div>

            {/* Google OAuth Button */}
            <Link
              href="/api/php/google-auth.php?action=login"
              className="w-full flex justify-center items-center gap-3 px-4 py-3 border border-slate-200 rounded-lg shadow-sm bg-white text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors no-underline"
            >
              <svg className="h-5 w-5" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
              <span>Continuar com Google</span>
            </Link>
          </form>

          <p className="mt-8 text-center text-sm text-slate-600">
            Não tem uma conta?{' '}
            <Link href="/register" className="font-semibold text-black hover:underline">
              Cadastre-se
            </Link>
          </p>
        </div>
      </div>

      <style jsx>{`
        .loading-spinner {
          width: 20px;
          height: 20px;
          border: 2px solid rgba(255,255,255,0.3);
          border-radius: 50%;
          border-top-color: #fff;
          animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
          to { transform: rotate(360deg); }
        }

        @keyframes fade-in {
          from {
            opacity: 0;
            transform: translateY(-10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }
        .animate-fade-in {
          animation: fade-in 0.5s ease-out;
        }

        .toggle-checkbox:checked {
          right: 0;
          border-color: #000000;
        }
        .toggle-checkbox:checked + .toggle-label {
          background-color: #000000;
        }
      `}</style>
    </div>
  )
}
