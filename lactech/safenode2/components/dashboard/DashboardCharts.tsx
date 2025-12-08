'use client'

import { useEffect, useRef } from 'react'
import { Chart, registerables } from 'chart.js'
import type { DashboardStats } from '@/types'

// Registrar componentes do Chart.js
if (typeof window !== 'undefined') {
  Chart.register(...registerables)
}

interface DashboardChartsProps {
  stats: DashboardStats | null
}

export default function DashboardCharts({ stats }: DashboardChartsProps) {
  const entitiesChartRef = useRef<HTMLCanvasElement>(null)
  const anomaliesChartRef = useRef<HTMLCanvasElement>(null)
  const entitiesChartInstance = useRef<Chart | null>(null)
  const anomaliesChartInstance = useRef<Chart | null>(null)

  useEffect(() => {
    // Entities Chart (Donut)
    if (entitiesChartRef.current && !entitiesChartInstance.current) {
      const ctx = entitiesChartRef.current.getContext('2d')
      if (ctx) {
        entitiesChartInstance.current = new Chart(ctx, {
          type: 'doughnut',
          data: {
            labels: ['Bom', 'Moderado', 'Ruim'],
            datasets: [{
              data: [60, 25, 15],
              backgroundColor: ['#ffffff', '#f59e0b', '#a855f7'],
              borderWidth: 0,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
            },
            cutout: '70%',
          },
        })
      }
    }

    // Anomalies Chart (Bar)
    if (anomaliesChartRef.current && !anomaliesChartInstance.current) {
      const ctx = anomaliesChartRef.current.getContext('2d')
      if (ctx) {
        anomaliesChartInstance.current = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            datasets: [{
              label: 'Anomalias',
              data: [12, 19, 15, 25, 22, 18, 14],
              backgroundColor: 'rgba(255, 255, 255, 0.1)',
              borderColor: 'rgba(255, 255, 255, 0.3)',
              borderWidth: 1,
            }],
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false,
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  color: '#a1a1aa',
                },
                grid: {
                  color: 'rgba(255, 255, 255, 0.05)',
                },
              },
              x: {
                ticks: {
                  color: '#a1a1aa',
                },
                grid: {
                  display: false,
                },
              },
            },
          },
        })
      }
    }

    return () => {
      if (entitiesChartInstance.current) {
        entitiesChartInstance.current.destroy()
        entitiesChartInstance.current = null
      }
      if (anomaliesChartInstance.current) {
        anomaliesChartInstance.current.destroy()
        anomaliesChartInstance.current = null
      }
    }
  }, [])

  return (
    <div className="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
      {/* Entities Overview (Donut Chart) */}
      <div className="lg:col-span-2 chart-card">
        <div className="flex items-center justify-between mb-8">
          <h3 className="text-lg font-semibold text-white">Visão Geral de Ameaças</h3>
        </div>
        <div className="flex items-center justify-center">
          <div className="relative" style={{ width: '220px', height: '220px' }}>
            <canvas ref={entitiesChartRef} width={220} height={220} />
            <div className="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
              <span className="text-5xl font-bold text-white">100</span>
              <span className="text-xs text-zinc-500 font-medium mt-1">Total Score</span>
            </div>
          </div>
        </div>
        <div className="flex items-center justify-center gap-8 mt-8">
          <div className="flex items-center gap-2.5">
            <span className="w-3 h-3 rounded-full bg-white"></span>
            <span className="text-sm text-zinc-400">Bom</span>
          </div>
          <div className="flex items-center gap-2.5">
            <span className="w-3 h-3 rounded-full bg-amber-500"></span>
            <span className="text-sm text-zinc-400">Moderado</span>
          </div>
          <div className="flex items-center gap-2.5">
            <span className="w-3 h-3 rounded-full bg-violet-500"></span>
            <span className="text-sm text-zinc-400">Ruim</span>
          </div>
        </div>
      </div>

      {/* Network Anomalies (Bar Chart) */}
      <div className="lg:col-span-3 chart-card">
        <div className="flex items-center justify-between mb-8">
          <h3 className="text-lg font-semibold text-white">Anomalias de Rede</h3>
          <div className="flex items-center gap-1 bg-white/5 rounded-xl p-1.5">
            <button className="period-btn">1S</button>
            <button className="period-btn active">1M</button>
            <button className="period-btn">1A</button>
          </div>
        </div>
        <div className="relative" style={{ height: '200px' }}>
          <canvas ref={anomaliesChartRef} />
        </div>
      </div>
    </div>
  )
}

