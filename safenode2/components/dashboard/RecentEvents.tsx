'use client'

import type { SecurityLog } from '@/types'

interface RecentEventsProps {
  logs: SecurityLog[]
}

export default function RecentEvents({ logs }: RecentEventsProps) {
  return (
    <div className="chart-card">
      <div className="flex items-center justify-between mb-6">
        <h3 className="text-lg font-semibold text-white">Eventos Recentes</h3>
        <span className="text-xs text-zinc-500 font-mono bg-white/5 px-3 py-1.5 rounded-lg">
          {new Date().toLocaleTimeString('pt-BR')}
        </span>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
        {logs.length === 0 ? (
          <div className="text-center py-10 text-zinc-500 col-span-2">
            <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
              <span className="text-white text-xl animate-spin">⟳</span>
            </div>
            <p className="text-sm font-medium">Carregando...</p>
          </div>
        ) : (
          logs.map((log) => (
            <div key={log.id} className="event-item">
              <div className="flex items-start justify-between mb-2">
                <div className="flex items-center gap-2">
                  <span
                    className={`w-2 h-2 rounded-full ${
                      log.action_taken === 'blocked'
                        ? 'bg-red-500'
                        : log.action_taken === 'challenged'
                        ? 'bg-yellow-500'
                        : 'bg-green-500'
                    }`}
                  ></span>
                  <span className="text-xs text-zinc-400 font-mono">
                    {new Date(log.created_at).toLocaleString('pt-BR')}
                  </span>
                </div>
                <span
                  className={`text-xs px-2 py-0.5 rounded ${
                    log.action_taken === 'blocked'
                      ? 'bg-red-500/20 text-red-400'
                      : log.action_taken === 'challenged'
                      ? 'bg-yellow-500/20 text-yellow-400'
                      : 'bg-green-500/20 text-green-400'
                  }`}
                >
                  {log.action_taken === 'blocked'
                    ? 'Bloqueado'
                    : log.action_taken === 'challenged'
                    ? 'Desafiado'
                    : 'Permitido'}
                </span>
              </div>
              <p className="text-sm text-white font-medium mb-1">{log.ip_address}</p>
              <p className="text-xs text-zinc-500 truncate">{log.request_uri}</p>
              {log.threat_type && (
                <p className="text-xs text-zinc-600 mt-1">Tipo: {log.threat_type}</p>
              )}
            </div>
          ))
        )}
      </div>
    </div>
  )
}
