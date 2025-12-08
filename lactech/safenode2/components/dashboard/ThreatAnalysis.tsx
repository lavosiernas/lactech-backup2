'use client'

import { ExternalLink } from 'lucide-react'

export default function ThreatAnalysis() {
  return (
    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      {/* Top Blocked IPs */}
      <div className="chart-card">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-lg font-semibold text-white">Top IPs Bloqueados</h3>
          <button className="text-zinc-600 hover:text-zinc-400 transition-colors">
            <ExternalLink className="w-4 h-4" />
          </button>
        </div>
        <div className="space-y-3">
          <div className="text-center py-10 text-zinc-500">
            <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
              <span className="text-white text-xl animate-spin">⟳</span>
            </div>
            <p className="text-sm font-medium">Carregando...</p>
          </div>
        </div>
      </div>

      {/* Top Countries */}
      <div className="chart-card">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-lg font-semibold text-white">Top Países</h3>
          <button className="text-zinc-600 hover:text-zinc-400 transition-colors">
            <ExternalLink className="w-4 h-4" />
          </button>
        </div>
        <div className="space-y-3">
          <div className="text-center py-10 text-zinc-500">
            <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
              <span className="text-white text-xl animate-spin">⟳</span>
            </div>
            <p className="text-sm font-medium">Carregando...</p>
          </div>
        </div>
      </div>
    </div>
  )
}


