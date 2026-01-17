import { Threat, ThreatType, Severity } from '../types/security';

interface Props {
  threats: Array<{
    id: number;
    type: ThreatType | string;
    severity: Severity | string;
    ip: string;
    uri: string;
    timestamp: string;
    pattern?: string;
  }>;
}

const getThreatTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    sql_injection: 'SQL Injection',
    xss: 'XSS',
    command_injection: 'Command Injection',
    path_traversal: 'Path Traversal',
    rce_php: 'RCE PHP',
    other: 'Outro',
  };
  return labels[type] || type;
};

const getSeverityColor = (severity: string | Severity): string => {
  const colors: Record<string, string> = {
    critical: 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    high: 'bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-800',
    medium: 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-800',
    low: 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
  };
  return colors[severity] || colors.low;
};

export default function RealTimeAlerts({ threats }: Props) {
  const recentThreats = threats.slice(0, 20);

  return (
    <div className="bg-white dark:bg-dark-900 rounded-xl border border-gray-200 dark:border-white/10 p-6">
      <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Alertas em Tempo Real
      </h2>
      <div className="space-y-3 max-h-96 overflow-y-auto">
        {recentThreats.length === 0 ? (
          <p className="text-gray-500 dark:text-zinc-500 text-center py-8">
            Nenhuma ameaça detectada no período selecionado
          </p>
        ) : (
          recentThreats.map((threat) => (
            <div
              key={threat.id}
              className={`border rounded-lg p-4 ${getSeverityColor(threat.severity)}`}
            >
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center gap-2 mb-1">
                    <span className="font-semibold">{getThreatTypeLabel(String(threat.type))}</span>
                    <span className="text-xs px-2 py-0.5 rounded bg-white/50 dark:bg-black/50">
                      {String(threat.severity).toUpperCase()}
                    </span>
                  </div>
                  <p className="text-sm mb-1">
                    <span className="font-medium">IP:</span> {threat.ip}
                  </p>
                  <p className="text-sm mb-1">
                    <span className="font-medium">Endpoint:</span> {threat.uri}
                  </p>
                  {threat.pattern && (
                    <p className="text-xs mt-2 opacity-75">
                      <span className="font-medium">Padrão:</span> {threat.pattern}
                    </p>
                  )}
                </div>
                <div className="text-xs text-gray-500 dark:text-zinc-500 ml-4">
                  {new Date(threat.timestamp).toLocaleString('pt-BR')}
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}

