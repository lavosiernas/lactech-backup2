import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

interface TimelineData {
  hour: string;
  threats: number;
  by_type: Record<string, number>;
}

interface Props {
  timeline: TimelineData[];
}

export default function ThreatTimeline({ timeline }: Props) {
  const chartData = timeline.map((item) => ({
    hora: item.hour,
    ameaças: item.threats,
    sql_injection: item.by_type.sql_injection || 0,
    xss: item.by_type.xss || 0,
    command_injection: item.by_type.command_injection || 0,
  }));

  return (
    <div className="bg-white dark:bg-dark-900 rounded-xl border border-gray-200 dark:border-white/10 p-6">
      <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Timeline de Ameaças
      </h2>
      <ResponsiveContainer width="100%" height={300}>
        <LineChart data={chartData}>
          <CartesianGrid strokeDasharray="3 3" className="stroke-gray-200 dark:stroke-white/10" />
          <XAxis 
            dataKey="hora" 
            className="text-gray-600 dark:text-zinc-400"
            tick={{ fill: 'currentColor' }}
          />
          <YAxis 
            className="text-gray-600 dark:text-zinc-400"
            tick={{ fill: 'currentColor' }}
          />
          <Tooltip 
            contentStyle={{
              backgroundColor: 'rgba(255, 255, 255, 0.95)',
              border: '1px solid #e5e7eb',
              borderRadius: '8px',
            }}
            labelStyle={{ color: '#111827' }}
          />
          <Legend />
          <Line 
            type="monotone" 
            dataKey="ameaças" 
            stroke="#ef4444" 
            strokeWidth={2}
            name="Total"
          />
          <Line 
            type="monotone" 
            dataKey="sql_injection" 
            stroke="#f97316" 
            strokeWidth={2}
            name="SQL Injection"
          />
          <Line 
            type="monotone" 
            dataKey="xss" 
            stroke="#eab308" 
            strokeWidth={2}
            name="XSS"
          />
          <Line 
            type="monotone" 
            dataKey="command_injection" 
            stroke="#8b5cf6" 
            strokeWidth={2}
            name="Command Injection"
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}

