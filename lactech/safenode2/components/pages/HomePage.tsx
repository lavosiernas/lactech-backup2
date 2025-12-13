'use client'

import { useEffect, useState } from 'react'
import { statsApi } from '@/lib/api'
import type { DashboardStats, IntegrationStats, SecurityLog } from '@/types'
import Link from 'next/link'
import { Shield, Activity, Zap, Globe, TrendingUp, AlertTriangle } from 'lucide-react'

export default function HomePage() {
  const [stats, setStats] = useState<DashboardStats | null>(null)
  const [integrationStats, setIntegrationStats] = useState<IntegrationStats | null>(null)
  const [recentLogs, setRecentLogs] = useState<SecurityLog[]>([])
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadData()
  }, [])

  const loadData = async () => {
    try {
      setLoading(true)
      
      // Carregar estatísticas principais
      const statsResponse = await statsApi.getIndexStats()
      if (statsResponse.success && statsResponse.data) {
        setStats(statsResponse.data as DashboardStats)
      }

      // Carregar estatísticas de integração
      const integrationResponse = await statsApi.getIntegrationStats()
      if (integrationResponse.success && integrationResponse.data) {
        setIntegrationStats(integrationResponse.data as IntegrationStats)
      }

      // Carregar logs recentes
      const logsResponse = await statsApi.getRecentLogs(5)
      if (logsResponse.success && logsResponse.data) {
        setRecentLogs(logsResponse.data as SecurityLog[])
      }
    } catch (error) {
      console.error('Erro ao carregar dados:', error)
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen bg-black flex items-center justify-center">
        <div className="text-white text-lg">Carregando...</div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-black text-white">
      {/* Header */}
      <header className="border-b border-gray-800">
        <div className="container mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex items-center gap-2">
            <Shield className="w-8 h-8 text-green-500" />
            <h1 className="text-2xl font-bold">SafeNode</h1>
          </div>
          <nav className="flex gap-6">
            <Link href="/login" className="hover:text-green-400 transition">
              Login
            </Link>
            <Link href="/register" className="hover:text-green-400 transition">
              Registrar
            </Link>
          </nav>
        </div>
      </header>

      {/* Hero Section */}
      <section className="container mx-auto px-4 py-20 text-center">
        <h2 className="text-5xl font-bold mb-4">
          Proteção Avançada para Seus Sites
        </h2>
        <p className="text-xl text-gray-400 mb-8 max-w-2xl mx-auto">
          Plataforma de segurança completa com proteção contra DDoS, bots maliciosos e ameaças em tempo real
        </p>
        <div className="flex gap-4 justify-center">
          <Link
            href="/register"
            className="bg-green-600 hover:bg-green-700 px-6 py-3 rounded-lg font-semibold transition"
          >
            Começar Agora
          </Link>
          <Link
            href="/login"
            className="border border-gray-700 hover:border-gray-600 px-6 py-3 rounded-lg font-semibold transition"
          >
            Fazer Login
          </Link>
        </div>
      </section>

      {/* Stats Section */}
      {stats && (
        <section className="container mx-auto px-4 py-12">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <StatCard
              icon={<Activity className="w-6 h-6" />}
              label="Requisições (24h)"
              value={stats.total_requests_24h.toLocaleString()}
              color="blue"
            />
            <StatCard
              icon={<Shield className="w-6 h-6" />}
              label="Ameaças Bloqueadas (24h)"
              value={stats.threats_blocked_24h.toLocaleString()}
              color="red"
            />
            <StatCard
              icon={<Zap className="w-6 h-6" />}
              label="Latência Média"
              value={stats.avg_latency ? `${stats.avg_latency}ms` : 'N/A'}
              color="green"
            />
            <StatCard
              icon={<TrendingUp className="w-6 h-6" />}
              label="Uptime"
              value={`${stats.uptime_percent}%`}
              color="green"
            />
          </div>
        </section>
      )}

      {/* Integration Stats */}
      {integrationStats && (
        <section className="container mx-auto px-4 py-12">
          <h3 className="text-2xl font-bold mb-6">Estatísticas de Integração</h3>
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <StatCard
              icon={<Globe className="w-6 h-6" />}
              label="Sites Protegidos"
              value={integrationStats.total_sites.toString()}
              color="blue"
            />
            <StatCard
              icon={<Activity className="w-6 h-6" />}
              label="Sites Ativos"
              value={integrationStats.active_sites.toString()}
              color="green"
            />
            <StatCard
              icon={<Shield className="w-6 h-6" />}
              label="Usuários"
              value={integrationStats.total_users.toString()}
              color="purple"
            />
            <StatCard
              icon={<Zap className="w-6 h-6" />}
              label="API Keys"
              value={integrationStats.total_api_keys.toString()}
              color="yellow"
            />
          </div>
        </section>
      )}

      {/* Recent Logs */}
      {recentLogs.length > 0 && (
        <section className="container mx-auto px-4 py-12">
          <h3 className="text-2xl font-bold mb-6">Eventos Recentes</h3>
          <div className="bg-gray-900 rounded-lg overflow-hidden">
            <table className="w-full">
              <thead className="bg-gray-800">
                <tr>
                  <th className="px-4 py-3 text-left">Data</th>
                  <th className="px-4 py-3 text-left">IP</th>
                  <th className="px-4 py-3 text-left">Ação</th>
                  <th className="px-4 py-3 text-left">Tipo de Ameaça</th>
                </tr>
              </thead>
              <tbody>
                {recentLogs.map((log) => (
                  <tr key={log.id} className="border-t border-gray-800">
                    <td className="px-4 py-3">
                      {new Date(log.created_at).toLocaleString('pt-BR')}
                    </td>
                    <td className="px-4 py-3 font-mono text-sm">{log.ip_address}</td>
                    <td className="px-4 py-3">
                      <span
                        className={`px-2 py-1 rounded text-xs ${
                          log.action_taken === 'blocked'
                            ? 'bg-red-900 text-red-200'
                            : log.action_taken === 'challenged'
                            ? 'bg-yellow-900 text-yellow-200'
                            : 'bg-green-900 text-green-200'
                        }`}
                      >
                        {log.action_taken === 'blocked'
                          ? 'Bloqueado'
                          : log.action_taken === 'challenged'
                          ? 'Desafiado'
                          : 'Permitido'}
                      </span>
                    </td>
                    <td className="px-4 py-3">{log.threat_type || 'N/A'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </section>
      )}

      {/* Footer */}
      <footer className="border-t border-gray-800 mt-20">
        <div className="container mx-auto px-4 py-8 text-center text-gray-400">
          <p>&copy; 2024 SafeNode. Todos os direitos reservados.</p>
        </div>
      </footer>
    </div>
  )
}

interface StatCardProps {
  icon: React.ReactNode
  label: string
  value: string
  color: 'blue' | 'green' | 'red' | 'yellow' | 'purple'
}

function StatCard({ icon, label, value, color }: StatCardProps) {
  const colorClasses = {
    blue: 'text-blue-400',
    green: 'text-green-400',
    red: 'text-red-400',
    yellow: 'text-yellow-400',
    purple: 'text-purple-400',
  }

  return (
    <div className="bg-gray-900 rounded-lg p-6 border border-gray-800">
      <div className={`${colorClasses[color]} mb-2`}>{icon}</div>
      <div className="text-3xl font-bold mb-1">{value}</div>
      <div className="text-sm text-gray-400">{label}</div>
    </div>
  )
}






