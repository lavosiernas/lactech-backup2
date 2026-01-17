import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { securityApi } from '../api/client';
import ThreatTimeline from '../components/ThreatTimeline';
import RealTimeAlerts from '../components/RealTimeAlerts';
import ThreatStats from '../components/ThreatStats';

export default function SecurityMonitor() {
  const [timeframe, setTimeframe] = useState<'24h' | '7d' | '30d'>('24h');
  const [autoRefresh, setAutoRefresh] = useState(true);

  const { data, isLoading, error, refetch } = useQuery({
    queryKey: ['threats', timeframe],
    queryFn: () => securityApi.getThreats(timeframe),
    refetchInterval: autoRefresh ? 10000 : false, // Atualizar a cada 10s se autoRefresh estiver ativo
  });

  useEffect(() => {
    if (autoRefresh) {
      const interval = setInterval(() => {
        refetch();
      }, 10000);
      return () => clearInterval(interval);
    }
  }, [autoRefresh, refetch]);

  return (
    <div className="min-h-screen bg-white dark:bg-dark-950">
      {/* Header */}
      <header className="bg-white/80 dark:bg-dark-900/50 backdrop-blur-xl border-b border-gray-200 dark:border-white/5 px-4 md:px-8 py-4">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
              Monitoramento em Tempo Real
            </h1>
            <p className="text-sm text-gray-600 dark:text-zinc-400 mt-1">
              Acompanhe ameaças e eventos de segurança em tempo real
            </p>
          </div>
          <div className="flex items-center gap-4">
            {/* Timeframe Selector */}
            <div className="flex gap-2 bg-gray-100 dark:bg-white/10 rounded-lg p-1">
              {(['24h', '7d', '30d'] as const).map((tf) => (
                <button
                  key={tf}
                  onClick={() => setTimeframe(tf)}
                  className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${
                    timeframe === tf
                      ? 'bg-white dark:bg-dark-800 text-gray-900 dark:text-white shadow-sm'
                      : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white'
                  }`}
                >
                  {tf === '24h' ? '24h' : tf === '7d' ? '7 dias' : '30 dias'}
                </button>
              ))}
            </div>
            {/* Auto Refresh Toggle */}
            <button
              onClick={() => setAutoRefresh(!autoRefresh)}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
                autoRefresh
                  ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-400'
                  : 'bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-zinc-400'
              }`}
            >
              {autoRefresh ? '🔄 Ativo' : '⏸ Pausado'}
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="p-4 md:p-8">
        {isLoading && (
          <div className="flex items-center justify-center h-64">
            <div className="text-gray-600 dark:text-zinc-400">Carregando...</div>
          </div>
        )}

        {error && (
          <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-4">
            <p className="text-red-600 dark:text-red-400">
              Erro ao carregar dados: {error instanceof Error ? error.message : 'Erro desconhecido'}
            </p>
          </div>
        )}

        {data && data.success && (
          <div className="space-y-6">
            {/* Stats Cards */}
            <ThreatStats stats={data.data.stats} />

            {/* Timeline */}
            <ThreatTimeline timeline={data.data.timeline} />

            {/* Real-time Alerts */}
            <RealTimeAlerts threats={data.data.threats.map(threat => ({
              id: threat.id,
              type: threat.type as any,
              severity: threat.severity as any,
              ip: threat.ip,
              uri: threat.uri,
              timestamp: threat.timestamp,
              pattern: threat.pattern,
            }))} />
          </div>
        )}
      </main>
    </div>
  );
}

