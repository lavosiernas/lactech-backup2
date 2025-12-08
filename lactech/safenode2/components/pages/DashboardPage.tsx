'use client'

import { useEffect, useState } from 'react'
import { useRouter } from 'next/navigation'
import { auth } from '@/lib/auth'
import { statsApi } from '@/lib/api'
import type { DashboardStats, SecurityLog } from '@/types'
import DashboardLayout from '@/components/layouts/DashboardLayout'
import StatCard from '@/components/dashboard/StatCard'
import DashboardCharts from '@/components/dashboard/DashboardCharts'
import DevicesTable from '@/components/dashboard/DevicesTable'
import RecentEvents from '@/components/dashboard/RecentEvents'
import QuickLinks from '@/components/dashboard/QuickLinks'
import ThreatAnalysis from '@/components/dashboard/ThreatAnalysis'

// Função auxiliar para formatar números de forma segura
const formatNumber = (value: number | undefined | null): string => {
  if (value === undefined || value === null || isNaN(value)) {
    return '-'
  }
  return value.toLocaleString('pt-BR')
}

export default function DashboardPage() {
  const router = useRouter()
  const [stats, setStats] = useState<DashboardStats | null>(null)
  const [recentLogs, setRecentLogs] = useState<SecurityLog[]>([])
  const [loading, setLoading] = useState(true)
  const [dashboardFlash, setDashboardFlash] = useState<{ message: string; type: string } | null>(null)

  useEffect(() => {
    if (!auth.isAuthenticated()) {
      router.push('/login')
      return
    }

    loadData()
    
    // Atualizar dados a cada 30 segundos
    const interval = setInterval(loadData, 30000)
    return () => clearInterval(interval)
  }, [router])

  const loadData = async () => {
    try {
      setLoading(true)
      
      const statsResponse = await statsApi.getIndexStats()
      if (statsResponse.success && statsResponse.data) {
        setStats(statsResponse.data as DashboardStats)
      }

      const logsResponse = await statsApi.getRecentLogs(10)
      if (logsResponse.success && logsResponse.data) {
        setRecentLogs(logsResponse.data as SecurityLog[])
      }
    } catch (error) {
      console.error('Erro ao carregar dados:', error)
    } finally {
      setLoading(false)
    }
  }

  return (
    <DashboardLayout>
      {/* Flash Message */}
      {dashboardFlash && (
        <div className={`mb-8 glass rounded-2xl p-5 ${
          dashboardFlash.type === 'warning' ? 'border-amber-500/30' : 
          dashboardFlash.type === 'error' ? 'border-red-500/30' : 
          'border-white/30'
        } flex items-center gap-4`}>
          <div className={`w-10 h-10 rounded-xl ${
            dashboardFlash.type === 'warning' ? 'bg-amber-500/20' : 
            dashboardFlash.type === 'error' ? 'bg-red-500/20' : 
            'bg-white/20'
          } flex items-center justify-center`}>
            <span className={`text-lg ${
              dashboardFlash.type === 'warning' ? 'text-amber-400' : 
              dashboardFlash.type === 'error' ? 'text-red-400' : 
              'text-white'
            }`}>
              {dashboardFlash.type === 'error' ? '⚠' : dashboardFlash.type === 'warning' ? '⚠' : '✓'}
            </span>
          </div>
          <p className={`font-medium ${
            dashboardFlash.type === 'warning' ? 'text-amber-200' : 
            dashboardFlash.type === 'error' ? 'text-red-200' : 
            'text-white'
          }`}>
            {dashboardFlash.message}
          </p>
        </div>
      )}

      {/* Development Notice Banner */}
      <div className="mb-8 glass rounded-2xl p-5 border-blue-500/30 bg-gradient-to-r from-blue-500/10 via-blue-500/5 to-transparent flex items-center gap-4">
        <div className="w-10 h-10 rounded-xl bg-blue-500/20 flex items-center justify-center flex-shrink-0">
          <span className="text-blue-400 text-lg">💻</span>
        </div>
        <div className="flex-1">
          <p className="font-semibold text-white text-sm mb-1">Sistema em Desenvolvimento Constante</p>
          <p className="text-xs text-zinc-400">O SafeNode está em evolução contínua. Novas funcionalidades e melhorias são adicionadas regularmente para garantir a melhor experiência e segurança.</p>
        </div>
      </div>

      {loading ? (
        <div className="text-center py-20">
          <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3 animate-spin">
            <span className="text-white text-xl">⟳</span>
          </div>
          <p className="text-sm font-medium text-zinc-500">Carregando...</p>
        </div>
      ) : (
        <>
          {/* Stats Cards */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 lg:gap-5 mb-8">
            <StatCard
              label="Total de Requisições"
              value={formatNumber(stats?.total_requests_24h)}
              change="+12%"
              changeType="positive"
              subtitle="comparado a ontem"
            />
            <StatCard
              label="Requisições Bloqueadas"
              value={formatNumber(stats?.threats_blocked_24h)}
              change={stats && stats.total_requests_24h > 0 
                ? `Taxa: ${((stats.threats_blocked_24h / stats.total_requests_24h) * 100).toFixed(1)}%`
                : 'Taxa: 0%'}
              changeType="negative"
              subtitle={stats && stats.total_requests_24h > 0 
                ? `Taxa: ${((stats.threats_blocked_24h / stats.total_requests_24h) * 100).toFixed(1)}%`
                : 'Taxa: 0%'}
            />
            <StatCard
              label="Visitantes Únicos"
              value="1,234"
              change="+5%"
              changeType="positive"
              subtitle="últimas 24h"
            />
            <StatCard
              label="IPs Bloqueados"
              value="89"
              change="ativos"
              changeType="warning"
              subtitle="últimos 7 dias"
            />
          </div>

          {/* Charts Row */}
          <DashboardCharts stats={stats} />

          {/* Network Devices Table */}
          <DevicesTable />

          {/* Threat Analysis Section */}
          <ThreatAnalysis />

          {/* Quick Links */}
          <QuickLinks />

          {/* Recent Events */}
          <RecentEvents logs={recentLogs} />
        </>
      )}
    </DashboardLayout>
  )
}
