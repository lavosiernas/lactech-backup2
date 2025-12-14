'use client'

import { useState } from 'react'
import { Search, SlidersHorizontal } from 'lucide-react'

export default function DevicesTable() {
  const [deviceFilterOpen, setDeviceFilterOpen] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')

  return (
    <div className="table-card mb-8" style={{ overflow: 'visible !important' }}>
      <div className="table-header p-4 sm:p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4" style={{ overflow: 'visible !important', position: 'relative' }}>
        <h3 className="text-base sm:text-lg font-semibold text-white">Dispositivos de Rede</h3>
        <div className="flex items-center gap-2 sm:gap-3 w-full sm:w-auto relative">
          <div className="relative flex-1 sm:flex-initial sm:w-56">
            <Search className="w-4 h-4 absolute left-3 sm:left-4 top-1/2 -translate-y-1/2 text-zinc-500" />
            <input
              type="text"
              id="device-search"
              placeholder="Buscar por nome"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="bg-white/5 border border-white/10 rounded-xl py-2 sm:py-2.5 pl-10 sm:pl-11 pr-3 sm:pr-4 text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-white/30 focus:ring-2 focus:ring-white/10 w-full transition-all"
            />
          </div>
          <button className="btn-ghost flex items-center justify-center gap-2 text-xs sm:text-sm py-2 sm:py-2.5 px-3 sm:px-4 flex-shrink-0">
            <span className="hidden sm:inline">Buscar</span>
            <Search className="w-4 h-4 sm:hidden" />
          </button>
          <div className="relative">
            <button
              onClick={() => setDeviceFilterOpen(!deviceFilterOpen)}
              className="btn-ghost flex items-center justify-center gap-2 text-xs sm:text-sm py-2 sm:py-2.5 px-3 sm:px-4 flex-shrink-0"
            >
              <SlidersHorizontal className="w-4 h-4" />
              <span className="hidden sm:inline">Filtrar</span>
            </button>

            {/* Filter Modal */}
            {deviceFilterOpen && (
              <div
                className="absolute top-full right-0 mt-2 w-72 bg-[#050505] border border-white/10 rounded-xl shadow-2xl z-[9999] p-4"
                onClick={(e) => e.stopPropagation()}
              >
                <div className="space-y-4">
                  <div className="flex items-center justify-between mb-2">
                    <h4 className="text-sm font-semibold text-white">Filtros</h4>
                    <button
                      onClick={() => setDeviceFilterOpen(false)}
                      className="text-white hover:text-zinc-300 transition-colors bg-white/10 hover:bg-white/20 rounded-lg p-1.5"
                    >
                      <span className="text-lg">×</span>
                    </button>
                  </div>

                  <div className="relative">
                    <label className="block text-xs text-zinc-400 mb-2">Status de Health</label>
                    <select className="w-full bg-zinc-800/80 border-2 border-white/50 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-white/70 appearance-none cursor-pointer pr-8 hover:bg-zinc-700/80">
                      <option value="">Todos</option>
                      <option value="good">Bom</option>
                      <option value="moderate">Moderado</option>
                      <option value="bad">Ruim</option>
                      <option value="unavailable">Indisponível</option>
                    </select>
                  </div>

                  <div className="flex gap-2 pt-2">
                    <button
                      onClick={() => setDeviceFilterOpen(false)}
                      className="flex-1 bg-white text-black py-2 text-sm font-semibold rounded-lg hover:bg-white/90 transition-colors"
                    >
                      Aplicar
                    </button>
                    <button
                      onClick={() => setDeviceFilterOpen(false)}
                      className="flex-1 bg-white/10 text-white border border-white/20 py-2 text-sm font-semibold rounded-lg hover:bg-white/20 transition-colors"
                    >
                      Limpar
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Desktop Table */}
      <div className="hidden lg:block overflow-x-auto">
        <table className="w-full">
          <thead>
            <tr className="text-left text-xs text-zinc-500 uppercase tracking-wider border-b border-white/5">
              <th className="px-6 py-4 font-semibold">Health</th>
              <th className="px-6 py-4 font-semibold">Nome</th>
              <th className="px-6 py-4 font-semibold">Tipo</th>
              <th className="px-6 py-4 font-semibold">Origem</th>
              <th className="px-6 py-4 font-semibold">Response Time</th>
              <th className="px-6 py-4 font-semibold">Packet Loss</th>
              <th className="px-6 py-4 font-semibold">Ação</th>
            </tr>
          </thead>
          <tbody>
            <tr className="table-row">
              <td colSpan={7} className="px-6 py-12 text-center text-zinc-500">
                <div className="flex flex-col items-center">
                  <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mb-3">
                    <span className="text-white text-xl animate-spin">⟳</span>
                  </div>
                  <p className="text-sm font-medium">Carregando dispositivos...</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      {/* Mobile Cards */}
      <div className="lg:hidden p-4 space-y-3">
        <div className="text-center py-10 text-zinc-500">
          <div className="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center mx-auto mb-3">
            <span className="text-white text-xl animate-spin">⟳</span>
          </div>
          <p className="text-sm font-medium">Carregando dispositivos...</p>
        </div>
      </div>
    </div>
  )
}








