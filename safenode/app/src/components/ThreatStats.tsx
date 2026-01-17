import { ThreatStats as ThreatStatsType } from '../types/security';

interface Props {
  stats: ThreatStatsType;
}

export default function ThreatStats({ stats }: Props) {
  return (
    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
      {/* Total Threats */}
      <div className="bg-white dark:bg-dark-900 rounded-xl border border-gray-200 dark:border-white/10 p-6">
        <p className="text-sm text-gray-600 dark:text-zinc-400 mb-2">Total de Ameaças</p>
        <p className="text-3xl font-bold text-gray-900 dark:text-white">
          {stats.total_threats.toLocaleString()}
        </p>
      </div>

      {/* By Severity - Critical */}
      <div className="bg-white dark:bg-dark-900 rounded-xl border border-gray-200 dark:border-white/10 p-6">
        <p className="text-sm text-gray-600 dark:text-zinc-400 mb-2">Críticas</p>
        <p className="text-3xl font-bold text-red-600 dark:text-red-400">
          {stats.by_severity.critical || 0}
        </p>
      </div>

      {/* By Severity - High */}
      <div className="bg-white dark:bg-dark-900 rounded-xl border border-gray-200 dark:border-white/10 p-6">
        <p className="text-sm text-gray-600 dark:text-zinc-400 mb-2">Altas</p>
        <p className="text-3xl font-bold text-orange-600 dark:text-orange-400">
          {stats.by_severity.high || 0}
        </p>
      </div>

      {/* Top Endpoints */}
      <div className="bg-white dark:bg-dark-900 rounded-xl border border-gray-200 dark:border-white/10 p-6">
        <p className="text-sm text-gray-600 dark:text-zinc-400 mb-2">Endpoints Afetados</p>
        <p className="text-3xl font-bold text-gray-900 dark:text-white">
          {stats.top_endpoints?.length || 0}
        </p>
      </div>
    </div>
  );
}

