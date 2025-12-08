'use client'

import { ReactNode, useState, useEffect } from 'react'
import { useRouter, usePathname } from 'next/navigation'
import { auth, type AuthUser } from '@/lib/auth'
import Link from 'next/link'
import Image from 'next/image'
import { 
  LayoutDashboard, Globe, Activity, Cpu, Compass, BarChart3, Users2,
  ShieldCheck, Settings2, LifeBuoy, BookOpen, Menu, X, Search, Bell,
  ChevronsLeft, ChevronsRight
} from 'lucide-react'

interface DashboardLayoutProps {
  children: ReactNode
}

export default function DashboardLayout({ children }: DashboardLayoutProps) {
  const router = useRouter()
  const pathname = usePathname()
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false)
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [notificationsOpen, setNotificationsOpen] = useState(false)
  const [user, setUser] = useState<AuthUser | null>(null)
  const [mounted, setMounted] = useState(false)

  useEffect(() => {
    setMounted(true)
    const currentUser = auth.getUser()
    setUser(currentUser)
    
    if (!auth.isAuthenticated()) {
      router.push('/login')
      return
    }
  }, [router])

  // Evitar hydration mismatch - só renderizar após montar no cliente
  if (!mounted) {
    return (
      <div className="min-h-screen h-screen flex items-center justify-center bg-[#030303]">
        <div className="text-white">Carregando...</div>
      </div>
    )
  }

  // Se não tem usuário após montar, redirecionar
  if (!user) {
    return null
  }

  const menuItems = [
    { icon: LayoutDashboard, label: 'Home', href: '/dashboard', title: 'Home' },
    { icon: Globe, label: 'Gerenciar Sites', href: '/sites', title: 'Gerenciar Sites' },
    { icon: Activity, label: 'Network', href: '/security-analytics', title: 'Network' },
    { icon: Cpu, label: 'Kubernetes', href: '/behavior-analysis', title: 'Kubernetes' },
    { icon: Compass, label: 'Explorar', href: '/logs', title: 'Explorar' },
    { icon: BarChart3, label: 'Analisar', href: '/suspicious-ips', title: 'Analisar' },
    { icon: Users2, label: 'Grupos', href: '/attacked-targets', title: 'Grupos' },
  ]

  const systemItems = [
    { icon: ShieldCheck, label: 'Verificação Humana', href: '/human-verification', title: 'Verificação Humana' },
    { icon: Settings2, label: 'Configurações', href: '/settings', title: 'Configurações' },
    { icon: LifeBuoy, label: 'Ajuda', href: '/help', title: 'Ajuda' },
    { icon: BookOpen, label: 'Documentação', href: '/documentation', title: 'Documentação' },
  ]

  const isActive = (href: string) => pathname === href

  return (
    <div className="min-h-screen h-screen overflow-hidden flex bg-[#030303]">
      {/* Desktop Sidebar */}
      <aside 
        className={`sidebar h-full flex-shrink-0 flex flex-col hidden lg:flex transition-all duration-300 ease-in-out overflow-hidden ${
          sidebarCollapsed ? 'w-20' : 'w-72'
        }`}
      >
        {/* Logo */}
        <div className="p-4 border-b border-white/5 flex-shrink-0 relative">
          <div className={`flex items-center ${sidebarCollapsed ? 'justify-center flex-col gap-3' : 'justify-between'}`}>
            <div className={`flex items-center gap-3 ${sidebarCollapsed ? 'justify-center' : ''}`}>
              <img 
                src="/assets/img/logos (6).png" 
                alt="SafeNode Logo" 
                className="w-8 h-8 object-contain flex-shrink-0"
                onError={(e) => {
                  e.currentTarget.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"%3E%3Cpath d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/%3E%3C/svg%3E'
                }}
              />
              {!sidebarCollapsed && (
                <div className="overflow-hidden whitespace-nowrap">
                  <h1 className="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                  <p className="text-xs text-zinc-500 font-medium">Security Platform</p>
                </div>
              )}
            </div>
            <button 
              onClick={() => setSidebarCollapsed(!sidebarCollapsed)}
              className="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0"
            >
              {sidebarCollapsed ? (
                <ChevronsRight className="w-5 h-5" />
              ) : (
                <ChevronsLeft className="w-5 h-5" />
              )}
            </button>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
          {!sidebarCollapsed && (
            <p className="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">
              Menu Principal
            </p>
          )}
          
          {menuItems.map((item) => {
            const Icon = item.icon
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`nav-item ${isActive(item.href) ? 'active' : ''} ${
                  sidebarCollapsed ? 'justify-center px-2' : ''
                }`}
                title={sidebarCollapsed ? item.title : ''}
              >
                <Icon className="w-5 h-5 flex-shrink-0" />
                {!sidebarCollapsed && (
                  <span className="font-medium whitespace-nowrap">{item.label}</span>
                )}
              </Link>
            )
          })}

          <div className="pt-4 mt-4 border-t border-white/5">
            {!sidebarCollapsed && (
              <p className="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">
                Sistema
              </p>
            )}
            {systemItems.map((item) => {
              const Icon = item.icon
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={`nav-item ${isActive(item.href) ? 'active' : ''} ${
                    sidebarCollapsed ? 'justify-center px-2' : ''
                  }`}
                  title={sidebarCollapsed ? item.title : ''}
                >
                  <Icon className="w-5 h-5 flex-shrink-0" />
                  {!sidebarCollapsed && (
                    <span className="font-medium whitespace-nowrap">{item.label}</span>
                  )}
                </Link>
              )
            })}
          </div>
        </nav>

        {/* Upgrade Card */}
        {!sidebarCollapsed && (
          <div className="p-4 flex-shrink-0">
            <div className="upgrade-card">
              <h3 className="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
              <button className="w-full btn-primary py-2.5 text-sm">
                Upgrade Agora
              </button>
            </div>
          </div>
        )}
      </aside>

      {/* Mobile Sidebar Overlay */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 bg-black/80 z-40 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Mobile Sidebar */}
      <aside
        className={`fixed inset-y-0 left-0 w-72 sidebar h-full flex flex-col z-50 lg:hidden overflow-y-auto transition-transform duration-300 ${
          sidebarOpen ? 'translate-x-0' : '-translate-x-full'
        }`}
      >
        {/* Logo */}
        <div className="p-4 border-b border-white/5 flex-shrink-0 relative">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <img 
                src="/assets/img/logos (6).png" 
                alt="SafeNode Logo" 
                className="w-8 h-8 object-contain flex-shrink-0"
                onError={(e) => {
                  e.currentTarget.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"%3E%3Cpath d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/%3E%3C/svg%3E'
                }}
              />
              <div className="overflow-hidden whitespace-nowrap">
                <h1 className="font-bold text-white text-xl tracking-tight">SafeNode</h1>
                <p className="text-xs text-zinc-500 font-medium">Security Platform</p>
              </div>
            </div>
            <button
              onClick={() => setSidebarOpen(false)}
              className="text-zinc-600 hover:text-zinc-400 transition-colors flex-shrink-0"
            >
              <X className="w-5 h-5" />
            </button>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 p-4 space-y-1 overflow-y-auto overflow-x-hidden">
          <p className="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">
            Menu Principal
          </p>
          
          {menuItems.map((item) => {
            const Icon = item.icon
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`nav-item ${isActive(item.href) ? 'active' : ''}`}
                onClick={() => setSidebarOpen(false)}
              >
                <Icon className="w-5 h-5 flex-shrink-0" />
                <span className="font-medium whitespace-nowrap">{item.label}</span>
              </Link>
            )
          })}

          <div className="pt-4 mt-4 border-t border-white/5">
            <p className="text-xs font-semibold text-zinc-600 uppercase tracking-wider mb-3 px-3 whitespace-nowrap">
              Sistema
            </p>
            {systemItems.map((item) => {
              const Icon = item.icon
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={`nav-item ${isActive(item.href) ? 'active' : ''}`}
                  onClick={() => setSidebarOpen(false)}
                >
                  <Icon className="w-5 h-5 flex-shrink-0" />
                  <span className="font-medium whitespace-nowrap">{item.label}</span>
                </Link>
              )
            })}
          </div>
        </nav>

        {/* Upgrade Card */}
        <div className="p-4 flex-shrink-0">
          <div className="upgrade-card">
            <h3 className="font-semibold text-white text-sm mb-3">Ativar Pro</h3>
            <button className="w-full btn-primary py-2.5 text-sm">
              Upgrade Agora
            </button>
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 flex flex-col h-full overflow-hidden bg-[#030303]">
        {/* Header */}
        <header className="h-20 bg-[#050505]/50 backdrop-blur-xl border-b border-white/5 px-8 flex items-center justify-between flex-shrink-0">
          <div className="flex items-center gap-6">
            <button
              onClick={() => setSidebarOpen(!sidebarOpen)}
              className="lg:hidden text-zinc-400 hover:text-white transition-colors"
            >
              <Menu className="w-6 h-6" />
            </button>
            <div>
              <h2 className="text-2xl font-bold text-white tracking-tight">Dashboard</h2>
            </div>
          </div>

          <div className="flex items-center gap-4">
            {/* Search */}
            <div className="relative hidden md:block">
              <Search className="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500" />
              <input
                type="text"
                placeholder="Buscar..."
                className="search-input"
              />
            </div>

            {/* Notifications */}
            <button
              onClick={() => setNotificationsOpen(!notificationsOpen)}
              className="relative p-3 text-zinc-400 hover:text-white hover:bg-white/5 rounded-xl transition-all"
            >
              <Bell className="w-5 h-5" />
            </button>

            {/* Profile */}
            <button
              onClick={() => router.push('/profile')}
              className="flex items-center gap-3 p-2 hover:bg-white/5 rounded-xl transition-all group"
            >
              <div className="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-400 to-rose-500 flex items-center justify-center text-white font-bold text-sm shadow-lg group-hover:scale-105 transition-transform">
                {user.name ? user.name.charAt(0).toUpperCase() : user.email.charAt(0).toUpperCase()}
              </div>
            </button>
          </div>
        </header>

        {/* Scrollable Content */}
        <div className="flex-1 overflow-y-auto p-8">
          {children}
        </div>
      </main>
    </div>
  )
}
